<?php

use function Livewire\Volt\{state, computed, with, layout, usesPagination, mount};
use App\Models\Blog;
use App\Models\BlogCategory;

layout('frontend.layouts.app');
usesPagination();

state(['search' => '', 'categorySlug' => null]);

mount(fn(?string $category = null) => $this->categorySlug = $category);

$updatedSearch = fn() => $this->resetPage();

$blogs = computed(function () {
    // 1. Get all categories that are part of an active path from root
    $activeRoots = BlogCategory::whereNull('parent_id')->active()->get();
    $activeCategoryIds = collect();

    $collectActive = function ($categories) use (&$collectActive, &$activeCategoryIds) {
        foreach ($categories as $cat) {
            $activeCategoryIds->push($cat->id);
            $collectActive($cat->children()->active()->get());
        }
    };
    $collectActive($activeRoots);

    // 2. Query blogs that are active
    $query = Blog::active()->latest();

    // 3. Filter by category path (Blog must have at least one active path if it has categories)
    $query->where(function ($q) use ($activeCategoryIds) {
        $q->whereDoesntHave('categories')
            ->orWhereHas('categories', function ($catQ) use ($activeCategoryIds) {
                $catQ->whereIn('blog_categories.id', $activeCategoryIds);
            });
    });

    if ($this->search) {
        $query->where(function ($q) {
            $q->where('title', 'like', '%' . $this->search . '%')
                ->orWhere('content', 'like', '%' . $this->search . '%')
                ->orWhere('tags', 'like', '%' . $this->search . '%');
        });
    }

    if ($this->categorySlug) {
        $category = BlogCategory::where('slug', $this->categorySlug)->active()->first();

        if ($category && $category->isActivePath()) {
            $allCategoryIds = collect([$category->id]);
            $collectIds = function ($cat) use (&$collectIds, &$allCategoryIds) {
                // Only collect active children
                foreach ($cat->children()->active()->get() as $child) {
                    $allCategoryIds->push($child->id);
                    $collectIds($child);
                }
            };
            $collectIds($category);

            $query->whereHas('categories', function ($q) use ($allCategoryIds) {
                $q->whereIn('blog_categories.id', $allCategoryIds);
            });
        } else {
            // Invalid or inactive category requested
            $query->whereRaw('1 = 0');
        }
    }

    return $query->paginate(12);
});

$categories = computed(function () {
    $roots = BlogCategory::whereNull('parent_id')
        ->active()
        ->with(['children'])
        ->get();

    $filterCategories = function ($categories) use (&$filterCategories) {
        return $categories->filter(function ($category) use (&$filterCategories) {
            // Recursively filter children (only active ones)
            $filteredChildren = $filterCategories($category->children()->active()->get());

            // Update the relation
            $category->setRelation('children', $filteredChildren);

            // Keep if has own ACTIVE blogs OR has valid children
            return $category->blogs()->active()->exists() || $filteredChildren->isNotEmpty();
        });
    };

    return $filterCategories($roots);
});

$activePath = computed(function () {
    if (!$this->categorySlug)
        return [];

    $path = [];
    $current = BlogCategory::where('slug', $this->categorySlug)->first();

    while ($current) {
        $path[] = $current->id; // We keep tracking IDs for UI logic
        $current = $current->parent;
    }

    return $path;
});

$selectCategory = function ($slug) {
    if ($this->categorySlug === $slug) {
        $this->categorySlug = null;
    } else {
        $this->categorySlug = $slug;
    }
    $this->resetPage();
};

$totalActiveCount = computed(function () {
    $activeRoots = BlogCategory::whereNull('parent_id')->active()->get();
    $activeCategoryIds = collect();
    $collectActive = function ($categories) use (&$collectActive, &$activeCategoryIds) {
        foreach ($categories as $cat) {
            $activeCategoryIds->push($cat->id);
            $collectActive($cat->children()->active()->get());
        }
    };
    $collectActive($activeRoots);

    return Blog::active()
        ->where(function ($q) use ($activeCategoryIds) {
            $q->whereDoesntHave('categories')
                ->orWhereHas('categories', function ($catQ) use ($activeCategoryIds) {
                    $catQ->whereIn('blog_categories.id', $activeCategoryIds);
                });
        })->count();
});

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
                            <div class="space-y-4">
                                <a href="{{ route('blog.index') }}" wire:navigate
                                    class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-bold transition-all border-l-4 {{ is_null($this->categorySlug) ? 'bg-indigo-600/10 border-indigo-600 text-indigo-600 dark:bg-indigo-600/20 shadow-sm' : 'border-transparent text-slate-500 hover:bg-slate-100 dark:hover:bg-[#222330]' }}">
                                    <span>All Categories</span>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-black {{ is_null($this->categorySlug) ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">
                                        {{ $this->totalActiveCount }}
                                    </span>
                                </a>

                                @foreach($this->categories as $category)
                                    @include('frontend.pages.blog.components.category-item', ['category' => $category, 'categorySlug' => $this->categorySlug, 'activePath' => $this->activePath])
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

                                    <div class="absolute top-6 left-6 z-10 flex flex-wrap gap-2 pr-6">
                                        @foreach($blog->categories as $category)
                                            @php
                                                $fullTitle = trim($category->title);
                                                $words = preg_split('/\s+/', $fullTitle, -1, PREG_SPLIT_NO_EMPTY);
                                                $isLong = count($words) > 1;
                                                $shortTitle = $isLong ? $words[0] . '...' : $fullTitle;
                                            @endphp

                                            @if($isLong)
                                                <div x-data="{ hovered: false }" @mouseenter="hovered = true"
                                                    @mouseleave="hovered = false" class="relative">
                                                    <span
                                                        class="inline-block px-3 py-1 bg-white/90 dark:bg-[#2a2b3c]/90 backdrop-blur-md rounded-full text-[10px] font-bold uppercase tracking-widest text-slate-900 dark:text-white shadow-sm transition-all duration-500 ease-in-out overflow-hidden whitespace-nowrap cursor-default"
                                                        :class="hovered ? 'max-w-[400px]' : 'max-w-[150px]'">
                                                        <span x-show="!hovered" x-transition:enter.duration.200ms>{{ $shortTitle }}</span>
                                                        <span x-show="hovered" x-transition:enter.duration.300ms>{{ $fullTitle }}</span>
                                                    </span>
                                                </div>
                                            @else
                                                <span class="inline-block px-3 py-1 bg-white/90 dark:bg-[#2a2b3c]/90 backdrop-blur-md rounded-full text-[10px] font-bold uppercase tracking-widest text-slate-900 dark:text-white shadow-sm">
                                                    {{ $fullTitle }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
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