<?php

namespace App\Traits;

use App\Models\TouchFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;
use App\Settings\GeneralSettings;

trait HasFileManagerSync
{
    protected static function bootHasFileManagerSync(): void
    {
        static::updating(function ($model) {
            if ($model->isDirty('attachments')) {
                $model->oldAttachmentsForSync = $model->getOriginal('attachments');
            }
        });

        static::saved(function ($model) {
            $model->syncAttachmentsWithFileManager();
        });

        static::deleting(function ($model) {
            $model->cleanupFileManagerOnDeletion();
        });
    }

    public function syncAttachmentsWithFileManager(): void
    {
        $disk = Storage::disk('attachments');
        $resourceType = $this->getFileManagerFolderName();
        $recordId = $this->id;
        $expectedFolder = "{$resourceType}/{$recordId}/";

        // 1. Cleanup actually removed files
        if (isset($this->oldAttachmentsForSync) && is_array($this->oldAttachmentsForSync)) {
            $newAttachments = $this->attachments ?? [];
            $deletedFiles = array_diff($this->oldAttachmentsForSync, $newAttachments);

            foreach ($deletedFiles as $deletedFile) {
                TouchFile::unregisterFile($deletedFile);
            }
            $this->oldAttachmentsForSync = null;
        }

        $attachments = $this->attachments;
        if (empty($attachments) || !is_array($attachments)) {
            $this->cleanupEmptyFolder($resourceType, $recordId);
            return;
        }

        $finalPaths = [];
        $changed = false;

        foreach ($attachments as $attachment) {
            $attachment = str_replace('\\', '/', $attachment);

            $filename = basename($attachment);
            $cleanFilename = $filename;
            if (preg_match('/^(.+)-v\d+\.(.+)$/i', $filename, $matches)) {
                $cleanFilename = $matches[1] . '.' . $matches[2];
            }

            $isInCorrectPlace = str_starts_with($attachment, $expectedFolder);
            $needsRenaming = ($filename !== $cleanFilename);

            if ((!$isInCorrectPlace && $disk->exists($attachment)) || ($isInCorrectPlace && $needsRenaming)) {

                try {
                    $mime = $disk->mimeType($attachment) ?? '';
                } catch (Exception $e) {
                    $mime = '';
                }

                $type = TouchFile::determineFileType($mime, $attachment);
                $subDir = ($type === 'video') ? 'videos' : ($type === 'image' ? 'images' : 'files');
                $targetPath = "{$expectedFolder}{$subDir}/{$cleanFilename}";

                // Overwrite: Unregister ensures old thumbs are deleted if the file existed
                if ($disk->exists($targetPath) && $attachment !== $targetPath) {
                    TouchFile::unregisterFile($targetPath);
                }

                if ($isInCorrectPlace && $needsRenaming) {
                    $disk->move($attachment, $targetPath);
                    $oldRecord = TouchFile::where('path', $attachment)->first();
                    if ($oldRecord) {
                        $oldRecord->update(['path' => $targetPath, 'name' => $cleanFilename]);
                    } else {
                        TouchFile::registerFile($targetPath);
                    }
                } else {
                    if (!$disk->exists(dirname($targetPath))) {
                        $disk->makeDirectory(dirname($targetPath), 0755, true);
                    }
                    $disk->move($attachment, $targetPath);
                    TouchFile::registerFile($targetPath);
                }

                $finalPaths[] = $targetPath;
                $changed = true;
            } else {
                if ($disk->exists($attachment)) {
                    TouchFile::registerFile($attachment);
                }
                $finalPaths[] = $attachment;
            }
        }

        if ($changed) {
            $this->attachments = $finalPaths;
            $this->saveQuietly();
        }

        $this->cleanupEmptyFolder($resourceType, $recordId);
    }

    public function cleanupFileManagerOnDeletion(): void
    {
        $path = "{$this->getFileManagerFolderName()}/{$this->id}";
        $folder = TouchFile::where('path', $path)->where('is_folder', true)->first();
        if ($folder)
            $folder->delete();
        else
            Storage::disk('attachments')->deleteDirectory($path);
    }

    public static function getStorageFolder(): string
    {
        return Str::lower(class_basename(static::class));
    }

    protected function getFileManagerFolderName(): string
    {
        return $this->fileManagerFolder ?? static::getStorageFolder();
    }

    protected function cleanupEmptyFolder(string $resourceType, $id): void
    {
        $disk = Storage::disk('attachments');
        $path = "{$resourceType}/{$id}";
        if ($disk->exists($path) && empty($disk->allFiles($path))) {
            $folder = TouchFile::where('path', $path)->where('is_folder', true)->first();
            if ($folder)
                $folder->delete();
            else
                $disk->deleteDirectory($path);
        }
    }
    public function getThumbnailSizes(): array
    {
        $folder = $this->getFileManagerFolderName();
        $modelSizes = config("{$folder}.thumb_sizes");
        if (is_array($modelSizes) && !empty($modelSizes))
            return $modelSizes;

        $global = app(GeneralSettings::class)->thumbnail_sizes;
        if (is_array($global) && !empty($global))
            return $global;

        return config('touch-file-manager.thumb_sizes', [150]);
    }
}
