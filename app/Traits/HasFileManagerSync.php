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
                } catch (\Exception $e) {
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
                    if (function_exists('clearstatcache'))
                        clearstatcache(true, $disk->path($attachment));
                    $disk->move($attachment, $targetPath);
                    if (function_exists('clearstatcache'))
                        clearstatcache(true, $disk->path($targetPath));

                    usleep(100000); // 100ms for disk settling

                    $record = TouchFile::where('path', $targetPath)->first() ?? TouchFile::where('path', $attachment)->first();

                    if ($record) {
                        $record->update([
                            'path' => $targetPath,
                            'name' => $cleanFilename,
                            'edit_user_id' => auth()->id() ?? $this->edit_user_id ?? $this->user_id
                        ]);
                        if ($record->type === 'image')
                            $record->generateThumbnails();
                    } else {
                        TouchFile::registerFile($targetPath, auth()->id() ?? $this->user_id, auth()->id() ?? $this->edit_user_id);
                    }

                    $finalPaths[] = $targetPath;
                    $changed = true;

                } else {
                    if (function_exists('clearstatcache'))
                        clearstatcache(true, $disk->path($attachment));

                    if ($disk->exists($attachment)) {
                        if (!$disk->exists(dirname($targetPath))) {
                            $disk->makeDirectory(dirname($targetPath));
                        }

                        $disk->move($attachment, $targetPath);
                        if (function_exists('clearstatcache'))
                            clearstatcache(true, $disk->path($targetPath));

                        usleep(100000);

                        TouchFile::registerFile($targetPath, auth()->id() ?? $this->user_id, auth()->id() ?? $this->edit_user_id);

                        $finalPaths[] = $targetPath;
                        $changed = true;
                    } else {
                        if ($disk->exists($targetPath)) {
                            $finalPaths[] = $targetPath;
                            $changed = ($attachment !== $targetPath);
                            // Also ensure registered if it exists on disk
                            TouchFile::registerFile($targetPath, auth()->id() ?? $this->user_id, auth()->id() ?? $this->edit_user_id);
                        } else {
                            // Truly missing
                            // $finalPaths[] = $attachment;
                        }
                    }
                }
            } else {
                // Correct place, just ensure it is registered
                if ($disk->exists($attachment)) {
                    TouchFile::registerFile($attachment, $this->user_id, auth()->id() ?? $this->edit_user_id);
                    $finalPaths[] = $attachment;
                }
            }
        }

        // FORCE DATABASE UPDATE IF PATHS CHANGED (e.g. temp -> permanent)
        if ($changed) {
            $this->attachments = $finalPaths;

            \Illuminate\Support\Facades\DB::table($this->getTable())
                ->where($this->getKeyName(), $this->getKey())
                ->update(['attachments' => json_encode($finalPaths)]);
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

    public function getDefaultMediaUrl(): ?string
    {
        $folder = $this->getFileManagerFolderName();
        $config = config("{$folder}.default_media");

        if (!$config) {
            return null;
        }

        if (!empty($config['path'])) {
            return url($config['path']);
        }

        return $config['url'] ?? null;
    }
    public function getThumbnailPath($path = null): ?string
    {
        $path = $path ?? ($this->cover_media ?? ($this->path ?? null));

        if (!$path || (isset($this->is_folder) && $this->is_folder)) {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        $disk = \Illuminate\Support\Facades\Storage::disk('attachments');

        if (!$disk->exists($path)) {
            return null;
        }

        $dir = dirname($path);
        $filename = basename($path);
        $nameOnly = pathinfo($filename, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $thumbsDir = ($dir === '.' || $dir === '') ? 'thumbs' : "{$dir}/thumbs";

        // Determine type if not provided (e.g., Blog model doesn't have 'type' column)
        $type = $this->type ?? (method_exists($this, 'isVideo') && $this->isVideo($path) ? 'video' : (method_exists($this, 'isImage') && $this->isImage($path) ? 'image' : 'other'));

        if ($type === 'other') {
            $type = TouchFile::determineFileType($disk->mimeType($path) ?? '', $path);
        }

        $sizes = $this->getThumbnailSizes();
        rsort($sizes); // Prioritize larger/better quality

        if ($type === 'video') {
            $slugName = \Illuminate\Support\Str::slug($nameOnly);
            $searchNames = array_unique([$slugName, $nameOnly]);
            $imageExts = ['jpg', 'jpeg', 'png', 'webp'];

            foreach ($searchNames as $name) {
                // 1. Try with sizes
                foreach ($sizes as $size) {
                    foreach ($imageExts as $ext) {
                        $tPath = "{$thumbsDir}/{$name}_{$size}.{$ext}";
                        if ($disk->exists($tPath))
                            return $tPath;
                    }
                }
                // 2. Try without sizes (Legacy/Fallback)
                foreach ($imageExts as $ext) {
                    $tPath = "{$thumbsDir}/{$name}.{$ext}";
                    if ($disk->exists($tPath))
                        return $tPath;
                }
            }

            // 3. Fallback to first available image thumbnail in this record
            if (isset($this->attachments) && is_array($this->attachments)) {
                $firstImage = collect($this->attachments)->filter(fn($a) => $this->isImage($a))->first();
                if ($firstImage && $firstImage !== $path) {
                    return $this->getThumbnailPath($firstImage);
                }
            }
        }

        if ($type === 'image') {
            // 1. Try with sizes
            foreach ($sizes as $size) {
                // Same extension
                $tPath = "{$thumbsDir}/{$nameOnly}_{$size}.{$extension}";
                if ($disk->exists($tPath))
                    return $tPath;

                // Fallback to jpg if original was different
                if ($extension !== 'jpg') {
                    $tPath = "{$thumbsDir}/{$nameOnly}_{$size}.jpg";
                    if ($disk->exists($tPath))
                        return $tPath;
                }
            }

            // 2. Try without sizes (Legacy/Fallback)
            $legacyPaths = [
                "{$thumbsDir}/{$filename}",
                "{$thumbsDir}/{$nameOnly}.jpg",
                "{$thumbsDir}/{$nameOnly}.webp"
            ];
            foreach ($legacyPaths as $lPath) {
                if ($disk->exists($lPath))
                    return $lPath;
            }
        }

        // Return original if it's an image, or null if it's something else we can't thumb
        return ($type === 'image') ? $path : null;
    }

    public function getThumbnailUrl($path = null): ?string
    {
        $thumbPath = $this->getThumbnailPath($path);

        if ($thumbPath) {
            return Storage::disk('attachments')->url($thumbPath);
        }

        return null;
    }
    public function isVideo($path): bool
    {
        if (!$path)
            return false;
        return str_ends_with(strtolower($path), '.mp4') || str_ends_with(strtolower($path), '.webm');
    }

    public function isImage($path): bool
    {
        if (!$path)
            return false;
        return str_ends_with(strtolower($path), '.jpg') || str_ends_with(strtolower($path), '.jpeg') || str_ends_with(strtolower($path), '.png') || str_ends_with(strtolower($path), '.webp');
    }
}
