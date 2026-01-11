<?php

namespace App\Observers;

use App\Models\BlogCategory;
use Illuminate\Support\Facades\Storage;

class BlogCategoryObserver
{
    /**
     * Handle the BlogCategory "created" event.
     */
    public function created(BlogCategory $blogCategory): void
    {
        //
    }

    /**
     * Handle the BlogCategory "updated" event.
     */
    public function updated(BlogCategory $blogCategory): void
    {
        // Handle cascading language update
        if ($blogCategory->isDirty('language_id')) {
            $blogCategory->children()->each(function ($child) use ($blogCategory) {
                $child->update(['language_id' => $blogCategory->language_id]);
            });

            $blogCategory->blogs()->each(function ($blog) use ($blogCategory) {
                $blog->update(['language_id' => $blogCategory->language_id]);
            });
        }
    }

    /**
     * Handle the BlogCategory "deleted" event.
     */
    public function deleted(BlogCategory $blogCategory): void
    {
        // Attachments and children are handled in Model boot method or here.
        // Model boot method is preferred for consistency, but Observer is also fine.
        // I put logic in Model boot, so I won't duplicate it here unless necessary.
    }

    /**
     * Handle the BlogCategory "saved" event.
     */
    public function saved(BlogCategory $blogCategory): void
    {
        // Logic for Attachments (Thumbnails for videos)
        // Since we don't have FFmpeg, we will just log or leave a placeholder.
        // If we had FFmpeg, we would check $blogCategory->attachments for new video files and generate thumbs.
    }
}
