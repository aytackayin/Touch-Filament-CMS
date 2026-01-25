<?php

namespace App\Observers;

use App\Models\Blog;
use Illuminate\Support\Facades\Storage;
use Exception;

class BlogObserver
{
    /**
     * Handle the Blog "created" event.
     */
    public function created(Blog $blog): void
    {
        //
    }

    /**
     * Handle the Blog "updated" event.
     */
    public function updated(Blog $blog): void
    {
        //
    }

    /**
     * Handle the Blog "deleted" event.
     */
    public function deleted(Blog $blog): void
    {
        // Handled in Model boot
    }

    /**
     * Handle the Blog "saved" event.
     */

    public function saved(Blog $blog): void
    {
        if ($blog->wasRecentlyCreated) {
            $this->moveContentImages($blog);
        } else {
            $this->cleanupContentImages($blog);
        }

        // Clean up old livewire temp files after any save
        $this->cleanupOldLivewireTmpFiles();
    }

    protected function moveContentImages(Blog $blog): void
    {
        $content = $blog->content;
        if (empty($content)) {
            return;
        }

        // Find all images in the content
        // Pattern matches: src=".../attachments/blogs/temp/..."
        // We assume the URL structure matches the config
        $pattern = '/src="([^"]+?\/attachments\/' . Blog::getStorageFolder() . '\/temp\/[^"]+)"/';

        $newContent = preg_replace_callback($pattern, function ($matches) use ($blog) {
            $oldUrl = $matches[1];

            // Extract filename
            $filename = basename($oldUrl);

            // Paths
            $oldPath = Blog::getStorageFolder() . '/temp/' . $filename;
            $newPath = Blog::getStorageFolder() . '/' . $blog->id . '/content-images/' . $filename;

            // Move file if it exists
            if (Storage::disk('attachments')->exists($oldPath)) {
                Storage::disk('attachments')->move($oldPath, $newPath);
            }

            // Return new relative URL (no domain)
            // Assuming default attachments URL structure or just using relative path
            // The user requested "domain-less URL"
            return 'src="/attachments/' . $newPath . '"';
        }, $content);

        // Also ensure any other absolute URLs are made relative if needed, 
        // but primarily we handle the moved images here.

        if ($content !== $newContent) {
            $blog->content = $newContent;
            $blog->saveQuietly();
        }
    }

    protected function cleanupContentImages(Blog $blog): void
    {
        // Only run if content changed
        if (!$blog->isDirty('content')) {
            return;
        }

        $oldContent = $blog->getOriginal('content');
        $newContent = $blog->content;

        // Extract all image paths from old and new content
        $oldImages = $this->extractImagePaths($oldContent);
        $newImages = $this->extractImagePaths($newContent);

        // Find images present in old but missing in new
        $deletedImages = array_diff($oldImages, $newImages);

        foreach ($deletedImages as $imagePath) {
            // Check if it's a file we manage (in attachments disk)
            // Parse URL to handle http://domain.com/attachments/...
            $path = parse_url($imagePath, PHP_URL_PATH);

            if ($path && str_starts_with($path, '/attachments/')) {
                $relativePath = substr($path, strlen('/attachments/'));
                if (Storage::disk('attachments')->exists($relativePath)) {
                    Storage::disk('attachments')->delete($relativePath);
                }
            }
        }
    }

    protected function cleanupOldLivewireTmpFiles(): void
    {
        try {
            $disk = Storage::disk('local');

            // Check if livewire-tmp directory exists
            if (!$disk->exists('livewire-tmp')) {
                return;
            }

            // Get all files in livewire-tmp (including subdirectories)
            $files = $disk->allFiles('livewire-tmp');

            // Delete files older than 1 minute
            $cutoffTime = now()->subMinute()->timestamp;

            foreach ($files as $file) {
                try {
                    $lastModified = $disk->lastModified($file);

                    if ($lastModified < $cutoffTime) {
                        $disk->delete($file);
                    }
                } catch (Exception $e) {
                    // Continue on error for individual files
                    continue;
                }
            }

            // Clean up empty subdirectories
            $directories = $disk->directories('livewire-tmp');
            foreach ($directories as $directory) {
                if (empty($disk->allFiles($directory))) {
                    $disk->deleteDirectory($directory);
                }
            }
        } catch (Exception $e) {
            // Fail silently - don't break the save operation
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
