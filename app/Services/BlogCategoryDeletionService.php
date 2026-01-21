<?php

namespace App\Services;

use App\Models\BlogCategory;
use App\Models\Blog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use App\Models\TouchFile;

class BlogCategoryDeletionService
{
    /**
     * Delete a category and all its related data
     * 
     * @param BlogCategory $category
     * @return void
     * @throws \Exception
     */
    public function delete(BlogCategory $category): void
    {
        DB::transaction(function () use ($category) {
            // Step 1: Collect all categories to delete (parent + all descendants)
            $categoriesToDelete = $this->collectAllCategories($category);

            // Step 2: Handle blogs associated with these categories
            $this->handleAssociatedBlogs($categoriesToDelete);

            // Step 3: Delete attachments for all categories
            $this->deleteAttachments($categoriesToDelete);

            // Step 4: Delete all categories (cascade will handle pivot table)
            // Use forceDelete to bypass events and prevent infinite loop
            $this->deleteCategories($categoriesToDelete);
        });
    }

    /**
     * Collect the category and all its descendants recursively
     * 
     * @param BlogCategory $category
     * @return Collection
     */
    protected function collectAllCategories(BlogCategory $category): Collection
    {
        $categories = collect([$category]);

        // Get all descendants recursively using a more efficient approach
        $this->collectDescendants($category, $categories);

        return $categories;
    }

    /**
     * Recursively collect all descendant categories
     * 
     * @param BlogCategory $category
     * @param Collection $collection
     * @return void
     */
    protected function collectDescendants(BlogCategory $category, Collection &$collection): void
    {
        // Eager load children to avoid N+1 queries
        $children = $category->children()->with('children')->get();

        foreach ($children as $child) {
            $collection->push($child);
            $this->collectDescendants($child, $collection);
        }
    }

    /**
     * Handle blogs associated with categories being deleted
     * 
     * @param Collection $categories
     * @return void
     */
    protected function handleAssociatedBlogs(Collection $categories): void
    {
        $categoryIds = $categories->pluck('id')->toArray();

        // Get all unique blogs associated with these categories
        $blogs = Blog::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('blog_categories.id', $categoryIds);
        })->with('categories')->get();

        foreach ($blogs as $blog) {
            // Count how many categories this blog has that are NOT being deleted
            $remainingCategoriesCount = $blog->categories()
                ->whereNotIn('blog_categories.id', $categoryIds)
                ->count();

            if ($remainingCategoriesCount === 0) {
                // Blog has no other categories, safe to delete
                $this->deleteBlog($blog);
            }
            // If blog has other categories, the pivot records will be cleaned by cascade
        }
    }

    /**
     * Delete a blog and its attachments
     * 
     * @param Blog $blog
     * @return void
     */
    protected function deleteBlog(Blog $blog): void
    {
        // Cleanup TouchFileManager (this also deletes disk files)
        $folder = TouchFile::where('path', "blogs/{$blog->id}")->where('is_folder', true)->first();
        if ($folder) {
            $folder->delete();
        } else {
            // Fallback disk cleanup
            Storage::disk('attachments')->deleteDirectory("blogs/{$blog->id}");
        }

        // Detach all categories first to prevent cascade issues
        $blog->categories()->detach();

        // Force delete to bypass events (Blog's deleting event already handles detach)
        // We use query builder to avoid triggering events again
        DB::table('blogs')->where('id', $blog->id)->delete();
    }

    /**
     * Delete attachments for all categories
     * 
     * @param Collection $categories
     * @return void
     */
    protected function deleteAttachments(Collection $categories): void
    {
        foreach ($categories as $category) {
            if ($category->id) {
                $folder = TouchFile::where('path', "blog_categories/{$category->id}")->where('is_folder', true)->first();
                if ($folder) {
                    $folder->delete();
                } else {
                    Storage::disk('attachments')->deleteDirectory("blog_categories/{$category->id}");
                }
            }
        }
    }

    /**
     * Delete all categories using bulk operation
     * 
     * @param Collection $categories
     * @return void
     */
    protected function deleteCategories(Collection $categories): void
    {
        $categoryIds = $categories->pluck('id')->toArray();

        // First, detach all pivot records (though cascade should handle this)
        DB::table('categorizables')
            ->whereIn('category_id', $categoryIds)
            ->delete();

        // Use query builder to delete without triggering events (prevent infinite loop)
        DB::table('blog_categories')
            ->whereIn('id', $categoryIds)
            ->delete();
    }
}
