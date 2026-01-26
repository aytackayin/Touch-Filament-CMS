<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class YouTubeIntegrationController extends Controller
{
    /**
     * List categories for the Chrome extension selection (as a tree).
     */
    public function getCategories()
    {
        $categories = BlogCategory::orderBy('sort')->orderBy('title')->get();
        return response()->json($this->buildTree($categories));
    }

    private function buildTree($categories, $parentId = null)
    {
        $branch = [];
        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildTree($categories, $category->id);
                if ($children) {
                    $category['children'] = $children;
                }
                $branch[] = $category;
            }
        }
        return $branch;
    }

    /**
     * Create a blog post from YouTube data.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'video_id' => 'required|string',
            'description' => 'nullable|string',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:blog_categories,id',
            'note' => 'nullable|string',
        ]);

        // Construct content with embed and note
        $embedHtml = '<div class="video-container"><iframe width="560" height="315" src="https://www.youtube.com/embed/' . $validated['video_id'] . '" frameborder="0" allowfullscreen></iframe></div>';

        $content = $embedHtml;
        if (!empty($validated['note'])) {
            $content .= '<br><h3>Notlarım:</h3>' . nl2br(e($validated['note'])) . '<br>';
        }
        $description = e($validated['description']);
        // Linkify: Convert URLs to clickable links
        $description = preg_replace(
            '/(https?:\/\/[^\s]+)/',
            '<a href="$1" target="_blank" style="color: #6366f1; text-decoration: underline;">$1</a>',
            $description
        );
        $content .= '<h3>YouTube Açıklaması:</h3>' . nl2br($description);

        $blog = Blog::create([
            'user_id' => auth()->id(),
            'language_id' => 1, // Default Language
            'title' => $validated['title'],
            'slug' => Blog::generateUniqueSlug($validated['title']),
            'content' => $content,
            'is_published' => false,
        ]);

        $blog->categories()->sync($validated['category_ids']);

        // Extract and Save Hashtags as Tags
        if (!empty($validated['description'])) {
            preg_match_all('/#(\w+)/u', $validated['description'], $matches);
            if (!empty($matches[1])) {
                $blog->update(['tags' => array_values(array_unique($matches[1]))]);
            }
        }

        // Download YouTube Cover Image and add to attachments
        try {
            $apiKey = $validated['video_id'];
            $thumbUrl = "https://i.ytimg.com/vi/{$apiKey}/maxresdefault.jpg";
            $imageResponse = \Illuminate\Support\Facades\Http::get($thumbUrl);

            if (!$imageResponse->successful()) {
                $thumbUrl = "https://i.ytimg.com/vi/{$apiKey}/hqdefault.jpg"; // Fallback
                $imageResponse = \Illuminate\Support\Facades\Http::get($thumbUrl);
            }

            if ($imageResponse->successful()) {
                $disk = \Illuminate\Support\Facades\Storage::disk('attachments');
                $folder = "blog/{$blog->id}/images";
                $filename = "youtube-cover.jpg";
                $path = "{$folder}/{$filename}";

                if (!$disk->exists($folder)) {
                    $disk->makeDirectory($folder, 0755, true);
                }

                $disk->put($path, $imageResponse->body());

                // Update blog attachments
                $blog->update(['attachments' => [$path]]);

                // Refresh to trigger sync logic if necessary or manual registration
                \App\Models\TouchFile::registerFile($path, auth()->id());
                $touchFile = \App\Models\TouchFile::where('path', $path)->first();
                if ($touchFile) {
                    $touchFile->generateThumbnails();
                }
            }
        } catch (\Exception $e) {
            // Silently fail image download
        }

        return response()->json([
            'message' => 'Blog başarıyla taslak olarak kaydedildi.',
            'blog_id' => $blog->id,
            'url' => url("/admin/blogs/{$blog->id}/edit"), // Direct link to edit in admin
        ], 201);
    }
}
