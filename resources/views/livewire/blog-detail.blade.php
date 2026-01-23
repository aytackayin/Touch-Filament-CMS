<?php

use function Livewire\Volt\{state, computed, layout, mount};
use App\Models\Blog;

layout('layouts.blog');

state(['blog' => null]);

mount(fn (string $slug) => 
    $this->blog = Blog::where('slug', $slug)->where('is_published', true)->firstOrFail()
);

$attachments = computed(fn() => collect($this->blog->attachments));

?>

<div x-data="{ 
    lightbox: false, 
    activeThumb: 0, 
    allMedia: {{ $this->attachments->map(fn($a) => Storage::disk('attachments')->url($a))->toJson() }}
}" class="pt-32 pb-24 bg-white dark:bg-slate-950 min-h-screen">
    
    <!-- Lightbox Overlay -->
    <template x-teleport="body">
        <div x-show="lightbox" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] bg-black/95 flex flex-col items-center justify-center p-4 lg:p-12"
             x-cloak>
            
            <div class="relative w-full h-full flex items-center justify-center">
                <!-- Close Button (Moved and Z-Indexed) -->
                <button @click="lightbox = false" 
                        class="absolute -top-4 -right-4 lg:top-0 lg:right-0 z-[110] text-white/50 hover:text-white transition-all p-4 bg-white/5 hover:bg-white/10 rounded-full">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <!-- Nav -->
                <button @click="activeThumb = (activeThumb - 1 + allMedia.length) % allMedia.length" class="absolute left-4 lg:left-8 text-white/50 hover:text-white p-4">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                
                <div class="max-w-7xl max-h-full overflow-hidden flex items-center justify-center">
                    <template x-for="(url, index) in allMedia" :key="index">
                        <div x-show="activeThumb === index"
                             x-transition:enter="transition ease-out duration-500"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="flex items-center justify-center">
                            
                            <!-- Video Check -->
                            <template x-if="url.endsWith('.mp4') || url.endsWith('.webm')">
                                <video :src="url" controls class="max-w-full max-h-[80vh] rounded-2xl shadow-2xl"></video>
                            </template>
                            
                            <!-- Image Check -->
                            <template x-if="!(url.endsWith('.mp4') || url.endsWith('.webm'))">
                                <img :src="url" class="max-w-full max-h-[80vh] rounded-2xl shadow-2xl object-contain">
                            </template>
                        </div>
                    </template>
                </div>

                <button @click="activeThumb = (activeThumb + 1) % allMedia.length" class="absolute right-4 lg:right-8 text-white/50 hover:text-white p-4">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>

            <!-- Thumbnails index -->
            <div class="mt-8 flex space-x-2">
                <template x-for="(url, index) in allMedia" :key="'thumb' + index">
                    <button @click="activeThumb = index" 
                            :class="activeThumb === index ? 'bg-white w-8' : 'bg-white/30 w-2 hover:bg-white/50'"
                            class="h-2 rounded-full transition-all duration-300"></button>
                </template>
            </div>
        </div>
    </template>

    <article class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-16">
        <!-- Main Media -->
        @php
            $mainImage = collect($blog->attachments)->filter(fn($a) => str_ends_with($a, '.jpg') || str_ends_with($a, '.png') || str_ends_with($a, '.webp'))->first();
            $mainVideo = collect($blog->attachments)->filter(fn($a) => str_ends_with($a, '.mp4') || str_ends_with($a, '.webm'))->first();
        @endphp

        @if($mainVideo || $mainImage)
            <div class="mb-16 rounded-[40px] overflow-hidden shadow-2xl aspect-[16/9] bg-slate-100 dark:bg-slate-900">
                @if($mainVideo)
                    <video src="{{ Storage::disk('attachments')->url($mainVideo) }}" controls class="w-full h-full object-cover"></video>
                @elseif($mainImage)
                    <img src="{{ Storage::disk('attachments')->url($mainImage) }}" class="w-full h-full object-cover" alt="{{ $blog->title }}">
                @endif
            </div>
        @endif

        <!-- Meta -->
        <div class="mb-12">
            <div class="flex items-center space-x-4 mb-6">
                @foreach($blog->categories as $category)
                    <span class="px-4 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-full text-xs font-bold uppercase tracking-widest">{{ $category->title }}</span>
                @endforeach
            </div>
            
            <h1 class="text-4xl md:text-6xl font-black mb-8 tracking-tight leading-tight text-slate-900 dark:text-white">
                {{ $blog->title }}
            </h1>

            <div class="flex items-center justify-between py-8 border-y border-slate-100 dark:border-slate-800">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-lg mr-4">
                        {{ strtoupper(substr($blog->user->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-sm font-black text-slate-900 dark:text-white">{{ $blog->user->name }}</div>
                        <div class="text-xs text-slate-500 font-medium">Author</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-black text-slate-900 dark:text-white">{{ $blog->created_at->format('M d, Y') }}</div>
                    <div class="text-xs text-slate-500 font-medium text-right uppercase tracking-tighter">Published Date</div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="prose prose-lg dark:prose-invert max-w-none mb-20 text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
            {!! $blog->content !!}
        </div>

        <!-- Attachments Grid -->
        @if($this->attachments->count() > 0)
            <div class="mt-24 pt-16 border-t border-slate-100 dark:border-slate-800">
                <h3 class="text-2xl font-black mb-10 tracking-tight">Gallery & Attachments</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4">
                    @foreach($this->attachments as $index => $attachment)
                        @php
                            $isImage = str_ends_with($attachment, '.jpg') || str_ends_with($attachment, '.png') || str_ends_with($attachment, '.webp');
                            $isVideo = str_ends_with($attachment, '.mp4') || str_ends_with($attachment, '.webm');
                            
                            $hasVideoThumb = false;
                            $videoThumbUrl = '';
                            if ($isVideo) {
                                $slugName = Str::slug(pathinfo($attachment, PATHINFO_FILENAME));
                                $thumbPath = "blogs/{$this->blog->id}/videos/thumbs/{$slugName}.jpg";
                                if (Storage::disk('attachments')->exists($thumbPath)) {
                                    $hasVideoThumb = true;
                                    $videoThumbUrl = Storage::disk('attachments')->url($thumbPath);
                                }
                            }
                        @endphp
                        <button @click="lightbox = true; activeThumb = {{ $index }}" 
                                class="group relative aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-900 focus:outline-none focus:ring-4 focus:ring-indigo-500/20">
                            
                            @if($isImage)
                                <img src="{{ Storage::disk('attachments')->url($attachment) }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            @elseif($hasVideoThumb)
                                <img src="{{ $videoThumbUrl }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                                    <svg class="w-10 h-10 text-white/80" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
                                </div>
                            @elseif($isVideo)
                                <div class="w-full h-full flex items-center justify-center bg-slate-200 dark:bg-slate-800">
                                    <svg class="w-10 h-10 text-slate-400" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
                                </div>
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-slate-200 dark:bg-slate-800">
                                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                </div>
                            @endif
                            
                            <div class="absolute inset-0 bg-indigo-600/0 group-hover:bg-indigo-600/20 transition-all duration-500 flex items-center justify-center">
                                <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transform scale-75 group-hover:scale-100 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </article>
</div>
