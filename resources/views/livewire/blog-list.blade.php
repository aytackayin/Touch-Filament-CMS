<?php

use function Livewire\Volt\{state, computed, with, layout, usesPagination};
use App\Models\Blog;
use App\Models\BlogCategory;

layout('layouts.blog');
usesPagination();

state(['search' => '', 'categoryId' => null]);

$updatedSearch = fn() => $this->resetPage();
$updatedCategoryId = fn() => $this->resetPage();

$blogs = computed(function () {
    $query = Blog::where('is_published', true)->latest();

    if ($this->search) {
        $query->where(function ($q) {
            $q->where('title', 'like', '%' . $this->search . '%')
                ->orWhere('content', 'like', '%' . $this->search . '%')
                ->orWhere('tags', 'like', '%' . $this->search . '%');
        });
    }

    if ($this->categoryId) {
        $query->whereHas('categories', function ($q) {
            $q->where('blog_categories.id', $this->categoryId);
        });
    }

    return $query->paginate(9);
});

$categories = computed(function () {
    // Top-level categories that have blogs or have descendants with blogs
    return BlogCategory::whereNull('parent_id')
        ->with(['allChildren'])
        ->get()
        ->filter(function ($category) {
            // Recursive check if this category or any descendant has blogs
            $hasBlogs = function ($cat) use (&$hasBlogs) {
                if ($cat->blogs()->exists())
                    return true;
                foreach ($cat->children as $child) {
                    if ($hasBlogs($child))
                        return true;
                }
                return false;
            };
            return $hasBlogs($category);
        });
});

$activePath = computed(function () {
    if (!$this->categoryId)
        return [];

    $path = [];
    $current = BlogCategory::find($this->categoryId);

    while ($current) {
        $path[] = $current->id;
        $current = $current->parent;
    }

    return $path;
});

$selectCategory = function ($id) {
    if ($this->categoryId === $id) {
        $this->categoryId = null;
    } else {
        $this->categoryId = $id;
    }
    $this->resetPage();
};

?>

<div class="pt-32 pb-24 bg-slate-50 dark:bg-[#222330] min-h-screen">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-16">

        <!-- Header -->
        <div class="mb-16 text-center">
            <h1 class="text-5xl font-black mb-6 tracking-tight">Our Blog</h1>
            <p class="text-slate-500 max-w-2xl mx-auto">Explore our collection of articles, tutorials, and insights.</p>
        </div>

        <div class="flex flex-col lg:flex-row gap-12">

            <!-- Sidebar (Categories) -->
            <aside class="w-full lg:w-1/4">
                <div class="sticky top-32">
                    <div class="glass-card rounded-3xl p-8">
                        <h3 class="text-lg font-bold mb-6 flex items-center">
                            <svg class="w-5 h-5 mr-3 text-indigo-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16m-7 6h7"></path>
                            </svg>
                            Categories
                        </h3>

                        <div class="space-y-4">
                            <button wire:click="$set('categoryId', null)"
                                class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-bold transition-all border-l-4 {{ is_null($this->categoryId) ? 'bg-indigo-600/10 border-indigo-600 text-indigo-600 dark:bg-indigo-600/20 shadow-sm' : 'border-transparent text-slate-500 hover:bg-slate-100 dark:hover:bg-[#222330]' }}">
                                <span>All Categories</span>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-black {{ is_null($this->categoryId) ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">
                                    {{ \App\Models\Blog::where('is_published', true)->count() }}
                                </span>
                            </button>

                            @foreach($this->categories as $category)
                                <x-blog.category-item :category="$category" :categoryId="$this->categoryId"
                                    :activePath="$this->activePath" />
                            @endforeach
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content (Blog List) -->
            <div class="w-full lg:w-3/4 xl:w-[82%]">

                <!-- Search -->
                <div class="mb-10 relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search articles..."
                        class="w-full px-8 py-5 rounded-full border-none bg-white dark:bg-[#2a2b3c] shadow-sm focus:ring-2 focus:ring-indigo-500 transition-all text-sm font-medium">
                    <div class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-8">
                    @forelse($this->blogs as $blog)
                        <a href="{{ route('blog.show', $blog->slug) }}" class="group block">
                            <article
                                class="flex flex-col h-full bg-white dark:bg-[#2a2b3c] rounded-3xl overflow-hidden shadow-sm hover:shadow-2xl dark:hover:shadow-[0_0_40px_-5px_rgba(79,70,229,0.3)] transition-all duration-500 transform hover:-translate-y-2 border border-slate-100 dark:border-[#404258]/30 dark:hover:border-indigo-500/50 transform-gpu isolate [backface-visibility:hidden] [transform-style:preserve-3d] will-change-transform">
                                <div
                                    class="relative h-60 overflow-hidden rounded-t-3xl [mask-image:-webkit-radial-gradient(white,black)]">
                                    @php
                                        $coverMedia = $blog->cover_media;
                                        $thumbUrl = $blog->getThumbnailUrl($coverMedia);
                                        $isVideo = $blog->isVideo($coverMedia);
                                    @endphp

                                    @if($thumbUrl || $blog->getDefaultMediaUrl())
                                        <img src="{{ $thumbUrl ?? $blog->getDefaultMediaUrl() }}"
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                            alt="{{ $blog->title }}">
                                        @if($isVideo)
                                            <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                                                <div
                                                    class="w-12 h-12 bg-white/30 backdrop-blur-md rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M8 5v14l11-7z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        <div
                                            class="w-full h-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center">
                                            <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-8">
                                    <h3
                                        class="text-xl font-bold mb-3 group-hover:text-indigo-600 transition-colors line-clamp-2 leading-tight">
                                        {{ $blog->title }}
                                    </h3>
                                    <div
                                        class="flex items-center gap-3 mb-4 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
                                        <div class="flex items-center gap-1">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            <span>{{ $blog->created_at->format('M d, Y') }}</span>
                                        </div>
                                        <span class="w-1 h-1 rounded-full bg-slate-200 dark:bg-slate-800"></span>
                                        <div class="flex items-center gap-1">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                </path>
                                            </svg>
                                            <span>{{ $blog->user->name }}</span>
                                        </div>
                                    </div>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm line-clamp-3 leading-relaxed">
                                        {{ Str::limit(strip_tags($blog->content), 120) }}
                                    </p>
                                </div>
                            </article>
                        </a>
                    @empty
                        <div class="col-span-full py-20 text-center">
                            <div
                                class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold mb-2">No articles found</h3>
                            <p class="text-slate-500">Try adjusting your search or filters.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                <div class="mt-16">
                    {{ $this->blogs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>