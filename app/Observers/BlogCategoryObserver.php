<?php

namespace App\Observers;

use App\Models\BlogCategory;

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

    public function saved(BlogCategory $blogCategory): void
    {
        // Attachments handled via HasFileManagerSync trait
    }
}
