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
    // Only categories with blogs (or children with blogs)
    return BlogCategory::where(function ($q) {
        $q->whereHas('blogs')
            ->orWhereHas('children', function ($cq) {
                $cq->whereHas('blogs');
            });
    })
        ->whereNull('parent_id')
        ->with([
            'children' => function ($q) {
                $q->whereHas('blogs');
            }
        ])
        ->get();
});

$selectCategory = function ($id) {
    if ($this->categoryId === $id) {
        $this->categoryId = null;
    } else {
        $this->categoryId = $id;
    }
    $this->resetPage(); // If using pagination traits, but here we are in Volt functional. 
    // Wait, Volt functional doesn't have resetPage() by default unless we use withPagination.
};

?>

<div class="pt-32 pb-24 bg-slate-50 dark:bg-slate-950 min-h-screen">
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
                                class="w-full text-left px-4 py-2 rounded-xl text-sm font-semibold transition-all {{ is_null($this->categoryId) ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                All Categories
                            </button>

                            @foreach($this->categories as $category)
                                <div x-data="{ open: false }" class="space-y-2">
                                    <div class="flex items-center">
                                        <button wire:click="$set('categoryId', {{ $category->id }})"
                                            class="flex-1 text-left px-4 py-2 rounded-xl text-sm font-semibold transition-all {{ $this->categoryId == $category->id ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                            {{ $category->title }}
                                        </button>
                                        @if($category->children->count() > 0)
                                            <button @click="open = !open" class="p-2 text-slate-400 hover:text-indigo-500">
                                                <svg class="w-4 h-4 transition-transform duration-300"
                                                    :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>

                                    @if($category->children->count() > 0)
                                        <div x-show="open" x-collapse class="pl-6 space-y-2">
                                            @foreach($category->children as $child)
                                                <button wire:click="$set('categoryId', {{ $child->id }})"
                                                    class="w-full text-left px-4 py-2 rounded-xl text-xs font-semibold transition-all {{ $this->categoryId == $child->id ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                                    {{ $child->title }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
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
                        class="w-full px-8 py-5 rounded-full border-none bg-white dark:bg-slate-900 shadow-sm focus:ring-2 focus:ring-indigo-500 transition-all text-sm font-medium">
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
                        <a href="{{ route('blog.show', $blog->slug) }}" class="group">
                            <article
                                class="flex flex-col h-full bg-white dark:bg-slate-900 rounded-3xl overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 border border-slate-100 dark:border-slate-800">
                                <div class="relative h-60 overflow-hidden">
                                    @php
                                        $image = collect($blog->attachments)->filter(fn($a) => str_ends_with($a, '.jpg') || str_ends_with($a, '.png') || str_ends_with($a, '.webp'))->first();
                                        $video = collect($blog->attachments)->filter(fn($a) => str_ends_with($a, '.mp4') || str_ends_with($a, '.webm'))->first();

                                        $hasVideoThumb = false;
                                        if ($video) {
                                            $slugName = Str::slug(pathinfo($video, PATHINFO_FILENAME));
                                            $thumbPath = "blogs/{$blog->id}/videos/thumbs/{$slugName}.jpg";
                                            $hasVideoThumb = Storage::disk('attachments')->exists($thumbPath);
                                        }
                                    @endphp

                                    @if($hasVideoThumb)
                                        <img src="{{ Storage::disk('attachments')->url($thumbPath) }}"
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                    @elseif($image)
                                        <img src="{{ Storage::disk('attachments')->url($image) }}"
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
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
                                    <div
                                        class="flex items-center space-x-4 mb-4 text-xs font-semibold text-slate-400 uppercase tracking-widest">
                                        <span>{{ $blog->created_at->format('M d, Y') }}</span>
                                        <span>•</span>
                                        <span>{{ $blog->user->name }}</span>
                                    </div>
                                    <h3
                                        class="text-xl font-bold mb-4 group-hover:text-indigo-600 transition-colors line-clamp-2 leading-tight">
                                        {{ $blog->title }}
                                    </h3>
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