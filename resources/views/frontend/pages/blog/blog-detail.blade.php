<?php

use function Livewire\Volt\{state, computed, layout, mount};
use App\Models\Blog;

layout('frontend.layouts.app');

state(['blog' => null]);

mount(fn (string $slug) => 
    $this->blog = Blog::where('slug', $slug)->where('is_published', true)->firstOrFail()
);

$attachments = computed(fn() => collect($this->blog->attachments ?? [])->reverse()->values());

?>

<div x-data="{ 
    lightbox: false, 
    activeThumb: 0, 
    allMedia: {{ $this->attachments->map(fn($a) => Storage::disk('attachments')->url($a))->toJson() }}
}" class="pb-24 bg-white dark:bg-[#222330] min-h-screen">
    
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

    @php
        $headerMedia = $blog->detail_header_media;
        $isVideo = $blog->isVideo($headerMedia);
        $mediaUrl = $blog->getMediaUrl($headerMedia) ?? $blog->getDefaultMediaUrl();
    @endphp

    @if($mediaUrl)
        <div class="w-full h-[250px] lg:h-[400px] relative overflow-hidden bg-slate-900 border-b border-white/10 shadow-2xl">
            @if($isVideo && $headerMedia)
                <video src="{{ $mediaUrl }}" class="w-full h-full object-cover" autoplay loop muted playsinline></video>
            @else
                <img src="{{ $mediaUrl }}" class="w-full h-full object-cover" alt="{{ $blog->title }}">
            @endif
            <div class="absolute inset-0 bg-black/20 dark:bg-black/40"></div>
        </div>
    @endif

    <article class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-16 {{ $headerMedia ? 'pt-12' : 'pt-32' }}">
        <!-- Meta -->
        <div class="mb-12">
            <div class="flex items-center space-x-4 mb-6">
                @foreach($blog->categories as $category)
                    <span class="px-4 py-1.5 bg-indigo-50 dark:bg-[#2a2b3c] text-indigo-600 dark:text-indigo-400 rounded-full text-xs font-bold uppercase tracking-widest">{{ $category->title }}</span>
                @endforeach
            </div>
            
            <h1 class="text-4xl md:text-6xl font-black mb-4 tracking-tight leading-tight text-slate-900 dark:text-white">
                {{ $blog->title }}
            </h1>

            <div class="flex items-center gap-3 mb-10 text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
                <div class="flex items-center gap-1.5">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>{{ $blog->created_at->format('M d, Y') }}</span>
                </div>
                <span class="w-1 h-1 rounded-full bg-slate-200 dark:bg-slate-800"></span>
                <div class="flex items-center gap-1.5">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    <span>{{ $blog->user->name }}</span>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="prose prose-lg dark:prose-invert max-w-none mb-20 text-slate-600 dark:text-slate-300 leading-relaxed font-medium"
            x-init="
                $el.querySelectorAll('pre').forEach(pre => {
                    if (pre.querySelector('.copy-button')) return;
                    
                    pre.style.position = 'relative';
                    pre.style.width = 'fit-content';
                    pre.style.maxWidth = '100%';
                    pre.style.whiteSpace = 'pre-wrap';
                    pre.style.wordBreak = 'break-all';
                    pre.style.paddingRight = '45px';
                    pre.classList.add('group/code');
                    
                    const btn = document.createElement('button');
                    btn.className = 'copy-button absolute top-3 right-3 p-2 text-white opacity-50 hover:opacity-100 transition-opacity duration-200 focus:outline-none z-10';
                    btn.innerHTML = '<svg class=\'w-4 h-4\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2\'></path></svg>';
                    
                    btn.onclick = () => {
                        const code = pre.querySelector('code')?.innerText || pre.innerText;
                        navigator.clipboard.writeText(code).then(() => {
                            const originalHTML = btn.innerHTML;
                            btn.innerHTML = '<svg class=\'w-4 h-4\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'></path></svg>';
                            
                            setTimeout(() => {
                                btn.innerHTML = originalHTML;
                            }, 2000);
                        });
                    };
                    
                    pre.appendChild(btn);
                });
            ">
            {!! $blog->content !!}
        </div>

        <!-- Attachments Grid -->
        @if($this->attachments->count() > 0)
            <div class="mt-24 pt-16 border-t border-slate-100 dark:border-slate-800">
                <h3 class="text-2xl font-black mb-10 tracking-tight">Gallery & Attachments</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4">
                    @foreach($this->attachments as $index => $attachment)
                        @php
                            $thumbUrl = $this->blog->getThumbnailUrl($attachment);
                            $isVideo = $this->blog->isVideo($attachment);
                            $isImage = $this->blog->isImage($attachment);
                        @endphp
                        <button @click="lightbox = true; activeThumb = {{ $index }}" 
                                class="group relative aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-[#2a2b3c] focus:outline-none focus:ring-4 focus:ring-indigo-500/20">
                            
                            @if($thumbUrl)
                                <img src="{{ $thumbUrl }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                @if($isVideo)
                                    <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                                        <svg class="w-10 h-10 text-white/80" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
                                    </div>
                                @endif
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
