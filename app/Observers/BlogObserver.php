<?php

namespace App\Observers;

use App\Models\Blog;
use App\Models\TouchFile;
use Illuminate\Support\Facades\Storage;
use Exception;

class BlogObserver
{
    /**
     * Handle the Blog "saved" event.
     */

    public function saved(Blog $blog): void
    {
        // 1. Cleanup images removed from original content
        $this->cleanupContentImages($blog);

        // 2. Move new temp images to permanent storage
        $this->moveContentImages($blog);

        // 3. Clean up old livewire temp files after any save
        $this->cleanupOldLivewireTmpFiles();
    }

    protected function moveContentImages(Blog $blog): void
    {
        $content = $blog->content;
        if (empty($content)) {
            return;
        }

        $storageFolder = Blog::getStorageFolder();
        // Match images in temporary directory: /attachments/blog/temp/.../content-images/...
        $pattern = '/src="([^">]*?\/attachments\/' . $storageFolder . '\/temp\/([^\/]+)\/content-images\/([^"]+))"/';

        $newContent = preg_replace_callback($pattern, function ($matches) use ($blog, $storageFolder) {
            $oldUrl = $matches[1];
            $foundUserId = $matches[2];
            $filename = basename($oldUrl);

            $oldPath = "{$storageFolder}/temp/{$foundUserId}/content-images/{$filename}";
            $newPath = "{$storageFolder}/{$blog->id}/content-images/{$filename}";

            if (Storage::disk('attachments')->exists($oldPath)) {
                if (!Storage::disk('attachments')->exists(dirname($newPath))) {
                    Storage::disk('attachments')->makeDirectory(dirname($newPath));
                }
                Storage::disk('attachments')->move($oldPath, $newPath);

                // Register the file to generate thumbnails and track in file manager
                TouchFile::registerFile($newPath, auth()->id() ?? $blog->user_id);
            }

            return 'src="/attachments/' . $newPath . '"';
        }, $content);

        if ($content !== $newContent) {
            $blog->content = $newContent;
            $blog->saveQuietly();
        }

        // Cleanup: remove the user's entire temp folder
        $cleanupUserId = auth()->id() ?? 'guest';
        $userTempDir = "{$storageFolder}/temp/{$cleanupUserId}";
        if (Storage::disk('attachments')->exists($userTempDir)) {
            Storage::disk('attachments')->deleteDirectory($userTempDir);
        }
    }

    protected function cleanupContentImages(Blog $blog): void
    {
        // Use getOriginal to see what was there before the user began editing
        $oldContent = $blog->getOriginal('content');
        $newContent = $blog->content;

        if (empty($oldContent) || $oldContent === $newContent) {
            return;
        }

        $oldImages = $this->extractImagePaths($oldContent);
        $newImages = $this->extractImagePaths($newContent);

        // Files present in old content but NOT in current content
        $deletedImages = array_diff($oldImages, $newImages);
        $storageFolder = Blog::getStorageFolder();
        $safePrefix = "{$storageFolder}/{$blog->id}/content-images/";

        foreach ($deletedImages as $imagePath) {
            $path = parse_url($imagePath, PHP_URL_PATH);

            if ($path && str_starts_with($path, '/attachments/')) {
                $relativePath = substr($path, strlen('/attachments/'));

                // Only clean up if it belongs to this blog
                if (str_starts_with($relativePath, $safePrefix)) {
                    // Use unregisterFile to clean up record, file, AND all thumbnails
                    TouchFile::unregisterFile($relativePath);
                }
            }
        }
    }

    protected function cleanupOldLivewireTmpFiles(): void
    {
        try {
            $disk = Storage::disk('local');

            if (!$disk->exists('livewire-tmp')) {
                return;
            }

            $files = $disk->allFiles('livewire-tmp');
            $cutoffTime = now()->subMinute()->timestamp;

            foreach ($files as $file) {
                try {
                    if ($disk->lastModified($file) < $cutoffTime) {
                        $disk->delete($file);
                    }
                } catch (Exception $e) {
                    continue;
                }
            }

            // Cleanup empty directories
            foreach ($disk->directories('livewire-tmp') as $directory) {
                if (empty($disk->allFiles($directory))) {
                    $disk->deleteDirectory($directory);
                }
            }
        } catch (Exception $e) {
            // Fail silently
        }
    }

    protected function extractImagePaths(?string $content): array
    {
        if (empty($content)) {
            return [];
        }

        $matches = [];
        preg_match_all('/src="([^"]+)"/', $content, $matches);
        return $matches[1] ?? [];
    }
}
