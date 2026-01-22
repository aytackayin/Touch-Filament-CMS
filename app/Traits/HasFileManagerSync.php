<?php

namespace App\Traits;

use App\Models\TouchFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;

trait HasFileManagerSync
{
    /**
     * Initialize filesystem synchronization hooks
     */
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

    /**
     * Sync attachments with TouchFileManager
     */
    public function syncAttachmentsWithFileManager(): void
    {
        $disk = Storage::disk('attachments');
        $resourceType = $this->getFileManagerFolderName();
        $recordId = $this->id;

        // 1. Cleanup deleted files
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
            // If no attachments left, try to cleanup empty folder
            $this->cleanupEmptyFolder($resourceType, $recordId);
            return;
        }

        // 2. Process current attachments
        $manager = class_exists(ImageManager::class) ? new ImageManager(new Driver()) : null;
        $finalAttachments = [];
        $hasChanges = false;

        foreach ($attachments as $attachment) {
            if (str_contains($attachment, '/temp/')) {
                // Determine sub-directory (images/videos)
                try {
                    $mimeType = $disk->mimeType($attachment);
                } catch (Exception $e) {
                    $mimeType = '';
                }

                $isImage = str_starts_with($mimeType, 'image/');
                $subDir = $isImage ? 'images' : 'videos';
                $filename = basename($attachment);
                $newPath = "{$resourceType}/{$recordId}/{$subDir}/{$filename}";

                if ($disk->exists($attachment)) {
                    // Ensure directory exists
                    if (!$disk->exists(dirname($newPath))) {
                        $disk->makeDirectory(dirname($newPath), 0755, true);
                    }

                    // Move file
                    $disk->move($attachment, $newPath);
                    TouchFile::registerFile($newPath);

                    // Image thumbnails
                    if ($isImage && $manager) {
                        $this->generateImageThumbnail($newPath, $manager);
                    }

                    $finalAttachments[] = $newPath;
                    $hasChanges = true;
                }
            } else {
                // Existing file, ensure it's registered
                if ($disk->exists($attachment)) {
                    TouchFile::registerFile($attachment);
                }
                $finalAttachments[] = $attachment;
            }
        }

        if ($hasChanges) {
            $this->attachments = $finalAttachments;
            $this->saveQuietly();
        }

        // Cleanup empty folder if needed
        $this->cleanupEmptyFolder($resourceType, $recordId);
    }

    /**
     * Cleanup everything related to this model on deletion
     */
    public function cleanupFileManagerOnDeletion(): void
    {
        $resourceType = $this->getFileManagerFolderName();
        $path = "{$resourceType}/{$this->id}";

        $folder = TouchFile::where('path', $path)->where('is_folder', true)->first();
        if ($folder) {
            // This will recursively delete child records and disk files
            $folder->delete();
        } else {
            // Fallback: Delete disk if record missing
            Storage::disk('attachments')->deleteDirectory($path);
        }
    }

    /**
     * Get the folder name for this resource (e.g., 'blogs', 'blog_categories')
     */
    protected function getFileManagerFolderName(): string
    {
        if (isset($this->fileManagerFolder)) {
            return $this->fileManagerFolder;
        }

        return Str::snake(Str::plural(class_basename($this)));
    }

    /**
     * Helper to clean up empty directory
     */
    protected function cleanupEmptyFolder(string $resourceType, $id): void
    {
        $disk = Storage::disk('attachments');
        $path = "{$resourceType}/{$id}";

        if ($disk->exists($path) && empty($disk->allFiles($path))) {
            $folder = TouchFile::where('path', $path)->where('is_folder', true)->first();
            if ($folder) {
                $folder->delete();
            } else {
                $disk->deleteDirectory($path);
            }
        }
    }

    /**
     * Helper to generate image thumbnails
     */
    protected function generateImageThumbnail(string $path, ImageManager $manager): void
    {
        $disk = Storage::disk('attachments');
        $filename = basename($path);
        $dir = dirname($path);
        $thumbsDir = $dir . '/thumbs';

        if (!$disk->exists($thumbsDir)) {
            $disk->makeDirectory($thumbsDir, 0755, true);
        }

        try {
            $image = $manager->read($disk->path($path));
            $image->scale(width: 150);
            $image->save($disk->path($thumbsDir . '/' . $filename));
        } catch (Exception $e) {
            // Fail silently
        }
    }
}
