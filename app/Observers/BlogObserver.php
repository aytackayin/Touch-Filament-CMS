<?php

namespace App\Observers;

use App\Models\Blog;
use Illuminate\Support\Facades\Storage;
use Exception;

class BlogObserver
{
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

        // Match images in temporary directory
        $pattern = '/src="([^"]+?\/attachments\/' . Blog::getStorageFolder() . '\/temp\/[^"]+)"/';

        $newContent = preg_replace_callback($pattern, function ($matches) use ($blog) {
            $oldUrl = $matches[1];
            $filename = basename($oldUrl);

            $oldPath = Blog::getStorageFolder() . '/temp/' . $filename;
            $newPath = Blog::getStorageFolder() . '/' . $blog->id . '/content-images/' . $filename;

            if (Storage::disk('attachments')->exists($oldPath)) {
                Storage::disk('attachments')->move($oldPath, $newPath);
            }

            return 'src="/attachments/' . $newPath . '"';
        }, $content);

        if ($content !== $newContent) {
            $blog->content = $newContent;
            $blog->saveQuietly();
        }
    }

    protected function cleanupContentImages(Blog $blog): void
    {
        if (!$blog->isDirty('content')) {
            return;
        }

        $oldImages = $this->extractImagePaths($blog->getOriginal('content'));
        $newImages = $this->extractImagePaths($blog->content);

        // Delete images removed from content
        $deletedImages = array_diff($oldImages, $newImages);

        foreach ($deletedImages as $imagePath) {
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
