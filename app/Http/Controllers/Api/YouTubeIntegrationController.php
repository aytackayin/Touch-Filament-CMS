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
     * List categories for the Chrome extension selection.
     */
    public function getCategories()
    {
        return response()->json(
            BlogCategory::select('id', 'title')->orderBy('title')->get()
        );
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
            'category_id' => 'required|exists:blog_categories,id',
            'note' => 'nullable|string',
        ]);

        // Construct content with embed and note
        $embedHtml = '<div class="video-container"><iframe width="560" height="315" src="https://www.youtube.com/embed/' . $validated['video_id'] . '" frameborder="0" allowfullscreen></iframe></div>';

        $content = $embedHtml;
        if (!empty($validated['note'])) {
            $content .= '<br><h3>Notlarım:</h3><blockquote style="border-left: 4px solid #6366f1; padding: 1rem; background: #f8fafc; font-style: italic;">' . nl2br(e($validated['note'])) . '</blockquote><br>';
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
            'language_id' => 1, // Default Language (TR usually)
            'title' => $validated['title'],
            'slug' => Blog::generateUniqueSlug($validated['title']),
            'content' => $content,
            'is_published' => false, // Save as draft by default
        ]);

        $blog->categories()->attach($validated['category_id']);

        return response()->json([
            'message' => 'Blog başarıyla taslak olarak kaydedildi.',
            'blog_id' => $blog->id,
            'url' => url("/admin/blogs/{$blog->id}/edit"), // Direct link to edit in admin
        ], 201);
    }
}
