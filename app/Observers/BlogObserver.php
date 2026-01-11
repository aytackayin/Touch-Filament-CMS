<?php

namespace App\Observers;

use App\Models\Blog;

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
        // Logic for Attachments (Thumbnails for videos)
    }
}
