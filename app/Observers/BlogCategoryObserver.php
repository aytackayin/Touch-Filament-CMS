<?php

namespace App\Observers;

use App\Models\BlogCategory;
use Illuminate\Support\Facades\Storage;
use App\Models\TouchFile;

class BlogCategoryObserver
{
    /**
     * Handle the BlogCategory "creating" event.
     */
    public function creating(BlogCategory $blogCategory): void
    {
        // Auto-generate slug if not provided
        if (empty($blogCategory->slug)) {
            $blogCategory->slug = BlogCategory::generateUniqueSlug($blogCategory->title);
        }
    }

    /**
     * Handle the BlogCategory "updating" event.
     */
    public function updating(BlogCategory $blogCategory): void
    {
        // Auto-generate slug if title changed and slug is empty
        if ($blogCategory->isDirty('title') && empty($blogCategory->slug)) {
            $blogCategory->slug = BlogCategory::generateUniqueSlug($blogCategory->title, $blogCategory->id);
        }

        // Store old attachments for cleanup
        if ($blogCategory->isDirty('attachments')) {
            $blogCategory->oldAttachmentsForCleanup = $blogCategory->getOriginal('attachments');
        }
    }

    /**
     * Handle the BlogCategory "updated" event.
     */
    public function updated(BlogCategory $blogCategory): void
    {
        // Handle cascading language update
        if ($blogCategory->isDirty('language_id')) {
            $newLang = $blogCategory->language_id;

            // Recursively update children
            $blogCategory->children()->each(function ($child) use ($newLang) {
                $child->update(['language_id' => $newLang]);
            });

            // Update associated blogs
            $blogCategory->blogs()->each(function ($blog) use ($newLang) {
                $blog->update(['language_id' => $newLang]);
            });
        }
    }

    /**
     * Handle the BlogCategory "saved" event.
     */
    public function saved(BlogCategory $blogCategory): void
    {
        $disk = Storage::disk('attachments');

        // Clean up deleted attachments
        if (isset($blogCategory->oldAttachmentsForCleanup) && is_array($blogCategory->oldAttachmentsForCleanup)) {
            $newAttachments = $blogCategory->attachments ?? [];
            $deletedFiles = array_diff($blogCategory->oldAttachmentsForCleanup, $newAttachments);

            foreach ($deletedFiles as $deletedFile) {
                // Delete main file
                if ($disk->exists($deletedFile)) {
                    $disk->delete($deletedFile);
                }
                TouchFile::unregisterFile($deletedFile);

                // Delete thumbnail
                $filename = basename($deletedFile);
                $thumbPath = "blog_categories/{$blogCategory->id}/images/thumbs/{$filename}";
                if ($disk->exists($thumbPath)) {
                    $disk->delete($thumbPath);
                }
            }

            $blogCategory->oldAttachmentsForCleanup = null;
        }

        $attachments = $blogCategory->attachments;
        if (empty($attachments) || !is_array($attachments)) {
            return;
        }

        $changed = false;
        $newAttachments = [];

        // Initialize Intervention Image Manager
        $manager = null;
        if (class_exists(\Intervention\Image\ImageManager::class)) {
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        }

        foreach ($attachments as $attachment) {
            if (str_contains($attachment, 'blog_categories/temp')) {
                // Handle new uploads from temp
                $filename = basename($attachment);
                $newPath = "blog_categories/{$blogCategory->id}/images/{$filename}";

                if ($disk->exists($attachment)) {
                    // Ensure directory exists
                    $directory = dirname($newPath);
                    if (!$disk->exists($directory)) {
                        $disk->makeDirectory($directory);
                    }

                    // Ensure thumbs directory exists
                    $thumbsDir = "blog_categories/{$blogCategory->id}/images/thumbs";
                    if (!$disk->exists($thumbsDir)) {
                        $disk->makeDirectory($thumbsDir);
                    }

                    // Move main file
                    $disk->move($attachment, $newPath);
                    TouchFile::registerFile($newPath);

                    // Generate thumbnail
                    if ($manager) {
                        try {
                            $fullPath = $disk->path($newPath);
                            $thumbPath = $disk->path("{$thumbsDir}/{$filename}");

                            $image = $manager->read($fullPath);
                            $image->scale(width: 150);
                            $image->save($thumbPath);
                        } catch (\Exception $e) {
                            // Fail silently
                        }
                    }

                    $newAttachments[] = $newPath;
                    $changed = true;
                } else {
                    $newAttachments[] = $attachment;
                    if ($disk->exists($attachment)) {
                        TouchFile::registerFile($attachment);
                    }
                }
            } else {
                // Handle existing files - check if thumbnail exists
                if ($disk->exists($attachment)) {
                    TouchFile::registerFile($attachment); // SYNC HERE

                    $filename = basename($attachment);
                    $thumbsDir = "blog_categories/{$blogCategory->id}/images/thumbs";
                    $thumbPath = "{$thumbsDir}/{$filename}";

                    // If main file exists but thumbnail doesn't, create it
                    if (!$disk->exists($thumbPath) && $manager) {
                        try {
                            // Ensure thumbs directory exists
                            if (!$disk->exists($thumbsDir)) {
                                $disk->makeDirectory($thumbsDir);
                            }

                            $fullPath = $disk->path($attachment);
                            $thumbFullPath = $disk->path($thumbPath);

                            $image = $manager->read($fullPath);
                            $image->scale(width: 150);
                            $image->save($thumbFullPath);
                        } catch (\Exception $e) {
                            // Fail silently
                        }
                    }
                }

                $newAttachments[] = $attachment;
            }
        }

        if ($changed) {
            $blogCategory->attachments = $newAttachments;
            $blogCategory->saveQuietly();
        }
    }
}
