<?php

use function Livewire\Volt\{state, computed, layout};
use App\Models\Blog;

layout('frontend.layouts.app');

$latestBlogs = computed(fn () => 
    Blog::where('is_published', true)
        ->latest()
        ->take(10)
        ->get()
);

$sliderBlogs = computed(fn () => 
    Blog::where('is_published', true)
        ->whereNotNull('attachments')
        ->latest()
        ->take(5)
        ->get()
);

?>

<div class="relative">
    <!-- Hero Slider -->
    <section x-data="{ 
        active: 0, 
        items: {{ $this->sliderBlogs->count() > 0 ? $this->sliderBlogs->count() : 3 }},
        autoPlay: true,
        init() {
            if (this.autoPlay) {
                setInterval(() => {
                    this.active = (this.active + 1) % this.items;
                }, 5000);
            }
        }
    }" class="h-screen w-full relative overflow-hidden bg-slate-900">
        
        @if($this->sliderBlogs->count() > 0)
            @foreach($this->sliderBlogs as $index => $blog)
                <div x-show="active === {{ $index }}" 
                     x-transition:enter="transition ease-out duration-1000"
                     x-transition:enter-start="opacity-0 scale-110"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-1000"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-90"
                     class="absolute inset-0 z-0">
                    <div class="absolute inset-0 bg-black/40 z-10"></div>
                    
                    @php
                        $slideMedia = $blog->slide_media;
                        $isVideo = $blog->isVideo($slideMedia);
                    @endphp

                    @if($isVideo)
                        <video x-ref="video{{ $index }}"
                               src="{{ $blog->getMediaUrl($slideMedia) }}" 
                               class="w-full h-full object-cover" 
                               autoplay loop muted playsinline>
                        </video>
                    @else
                        <img src="{{ $blog->getMediaUrl($slideMedia) ?? $blog->getDefaultMediaUrl() }}" 
                             class="w-full h-full object-cover" alt="{{ $blog->title }}">
                    @endif
                    
                    <div class="absolute inset-0 z-20 flex flex-col items-center justify-center text-center px-4">
                        <div class="max-w-4xl" 
                             x-intersect="
                                active === {{ $index }};
                                if (active === {{ $index }} && $refs.video{{ $index }}) {
                                    $refs.video{{ $index }}.play();
                                }
                             ">
                            <span class="inline-block px-4 py-1.5 mb-6 text-xs font-bold tracking-widest uppercase bg-indigo-600 text-white rounded-full">Featured Story</span>
                            <h1 class="text-5xl md:text-7xl font-black text-white mb-8 tracking-tight leading-tight">
                                {{ $blog->title }}
                            </h1>
                            <div class="flex items-center justify-center space-x-6 text-slate-300 text-sm font-medium">
                                <span class="flex items-center"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> {{ $blog->created_at->format('M d, Y') }}</span>
                                <span class="flex items-center"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg> {{ $blog->user->name }}</span>
                            </div>
                            <a href="{{ route('blog.show', $blog->slug) }}" class="mt-10 inline-flex items-center px-8 py-4 rounded-full bg-white text-slate-900 font-bold hover:bg-indigo-600 hover:text-white transition-all duration-300 transform hover:scale-105">
                                Read More
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <!-- Fallback Slider if no blogs yet -->
            <div class="absolute inset-0 bg-slate-950">
                <div class="absolute inset-0 bg-black/50 z-10"></div>
                <img src="{{ config('blog.default_media.path') ? url(config('blog.default_media.path')) : config('blog.default_media.url') }}" class="w-full h-full object-cover">
                <div class="absolute inset-0 z-20 flex flex-col items-center justify-center text-center px-4">
                    <h1 class="text-5xl md:text-7xl font-black text-white mb-8 tracking-tight leading-tight">Welcome to Our Blog</h1>
                    <p class="text-xl text-slate-300 max-w-2xl mx-auto">Discover stories, insights, and inspirations from our writers.</p>
                </div>
            </div>
        @endif

        <!-- Slider Controls -->
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 z-30 flex space-x-3">
            @if($this->sliderBlogs->count() > 0)
                @foreach($this->sliderBlogs as $index => $blog)
                    <button @click="active = {{ $index }}" 
                            :class="active === {{ $index }} ? 'w-12 bg-white' : 'w-3 bg-white/30'"
                            class="h-3 rounded-full transition-all duration-500"></button>
                @endforeach
            @endif
        </div>
    </section>

    <!-- Content Section -->
    <section class="py-24 bg-slate-50 dark:bg-[#222330]">
        <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-16">
            <div class="flex items-end justify-between mb-16">
                <div>
                    <h2 class="text-4xl font-black tracking-tight mb-4">Latest Stories</h2>
                    <div class="h-1.5 w-20 rounded-full premium-gradient"></div>
                </div>
                <a href="{{ route('blog.index') }}" class="group flex items-center text-sm font-bold tracking-widest uppercase hover:text-indigo-600 transition-colors">
                    View All Posts
                    <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>

            <!-- Blog Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-8">
                @foreach($this->latestBlogs as $blog)
                    <a href="{{ route('blog.show', $blog->slug) }}" class="group block">
                        <article class="flex flex-col h-full bg-white dark:bg-[#2a2b3c] rounded-3xl overflow-hidden shadow-sm hover:shadow-2xl dark:hover:shadow-[0_0_40px_-5px_rgba(79,70,229,0.3)] transition-all duration-500 transform hover:-translate-y-2 border border-slate-100 dark:border-[#404258]/30 dark:hover:border-indigo-500/50 transform-gpu isolate [backface-visibility:hidden] [transform-style:preserve-3d] will-change-transform">
                            <div class="relative h-64 overflow-hidden rounded-t-3xl [mask-image:-webkit-radial-gradient(white,black)]">
                                @php
                                    $coverMedia = $blog->cover_media;
                                    $thumbUrl = $blog->getThumbnailUrl($coverMedia);
                                    $isVideo = $blog->isVideo($coverMedia);
                                @endphp
                                
                                @if($thumbUrl || $blog->getDefaultMediaUrl())
                                    <img src="{{ $thumbUrl ?? $blog->getDefaultMediaUrl() }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="{{ $blog->title }}">
                                    @if($isVideo)
                                        <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                                            <div class="w-12 h-12 bg-white/30 backdrop-blur-md rounded-full flex items-center justify-center">
                                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <div class="w-full h-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center">
                                        <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                @endif
                                
                                <div class="absolute top-6 left-6">
                                    @foreach($blog->categories->take(1) as $category)
                                        <span class="px-3 py-1 bg-white/90 dark:bg-[#2a2b3c]/90 backdrop-blur-md rounded-full text-[10px] font-bold uppercase tracking-widest text-slate-900 dark:text-white">{{ $category->title }}</span>
                                    @endforeach
                                </div>
                            </div>
                            
                            <div class="p-8 flex-1 flex flex-col">
                                <h3 class="text-2xl font-bold mb-3 group-hover:text-indigo-600 transition-colors line-clamp-2 leading-tight">
                                    {{ $blog->title }}
                                </h3>
                                <div class="flex items-center gap-3 mb-4 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
                                    <div class="flex items-center gap-1">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <span>{{ $blog->created_at->format('M d, Y') }}</span>
                                    </div>
                                    <span class="w-1 h-1 rounded-full bg-slate-200 dark:bg-slate-800"></span>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        <span>{{ $blog->user->name }}</span>
                                    </div>
                                </div>
                                <p class="text-slate-500 dark:text-slate-400 text-sm line-clamp-3 leading-relaxed mb-6">
                                    {{ Str::limit(strip_tags($blog->content), 120) }}
                                </p>
                                
                                <!-- No button as requested, the card is the link -->
                            </div>
                        </article>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
</div>
