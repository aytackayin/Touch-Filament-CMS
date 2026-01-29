<?php

use function Livewire\Volt\{state, computed, layout, mount};
use App\Models\Blog;
use App\Models\TouchFile;
use Illuminate\Support\Facades\Storage;

layout('frontend.layouts.app');

state(['blog' => null]);

mount(function (string $slug) {
    $blog = Blog::where('slug', $slug)->active()->firstOrFail();

    // Eğer blog kategorilere bağlıysa, en az bir kategorinin aktif bir yolu olmalı
    $categories = $blog->categories;
    if ($categories->isNotEmpty()) {
        $hasActivePath = false;
        foreach ($categories as $category) {
            if ($category->isActivePath()) {
                $hasActivePath = true;
                break;
            }
        }
        
        if (!$hasActivePath) {
            abort(404);
        }
    }

    $this->blog = $blog;
});

$attachments = computed(function() {
    $paths = collect($this->blog->attachments ?? [])->reverse()->values();
    if ($paths->isEmpty()) return collect();

    $files = TouchFile::whereIn('path', $paths)->get()->keyBy('path');

    return $paths->map(function ($path) use ($files) {
        if ($file = $files->get($path)) {
            return $file;
        }
        
        // Fallback for files not in TouchFile table
        return new TouchFile([
            'path' => $path,
            'name' => basename($path),
            'type' => TouchFile::determineFileType('', $path),
            'size' => 0
        ]);
    });
});
?>

<!-- Attachments Logic -->
@php
    $allAttachments = $this->attachments;
    $mediaFiles = $allAttachments->filter(fn($file) => in_array($file->type, ['image', 'video']))->values();
    $otherFiles = $allAttachments->reject(fn($file) => in_array($file->type, ['image', 'video']))->values();
@endphp

<div x-data="{ 
    lightbox: false, 
    activeThumb: 0, 
    allMedia: {{ $mediaFiles->map(fn($file) => $file->url ?? Storage::disk('attachments')->url($file->path))->toJson() }},
    filePreview: false,
    filePreviewUrl: '',
    filePreviewName: ''
}" class="pb-24 bg-white dark:bg-[#222330] min-h-screen">
    
    <!-- Lightbox Overlay -->
    <template x-teleport="body">
        <div x-show="lightbox" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] bg-black/95 flex flex-col items-center justify-center p-4 lg:p-12">
            
            <div class="relative w-full h-full flex items-center justify-center">
                <!-- Close Button -->
                <button @click="lightbox = false" 
                        class="absolute -top-4 -right-4 lg:top-0 lg:right-0 z-[110] text-white/50 hover:text-white transition-all p-4 bg-white/5 hover:bg-white/10 rounded-full">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <!-- Nav -->
                <template x-if="allMedia.length > 1">
                    <div>
                        <button @click="activeThumb = (activeThumb - 1 + allMedia.length) % allMedia.length" class="absolute left-4 lg:left-8 z-[105] text-white/50 hover:text-white p-4">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        <button @click="activeThumb = (activeThumb + 1) % allMedia.length" class="absolute right-4 lg:right-8 z-[105] text-white/50 hover:text-white p-4">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                </template>
                
                <div class="max-w-7xl max-h-full overflow-hidden flex items-center justify-center">
                    <template x-for="(url, index) in allMedia" :key="index">
                        <div x-show="activeThumb === index"
                             x-transition:enter="transition ease-out duration-500"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="flex items-center justify-center w-full h-full">
                            
                            <!-- Video -->
                            <template x-if="url.endsWith('.mp4') || url.endsWith('.webm')">
                                <video :src="url" controls class="max-w-full max-h-[80vh] rounded-2xl shadow-2xl"></video>
                            </template>
                            
                            <!-- Image -->
                            <template x-if="!(url.endsWith('.mp4') || url.endsWith('.webm'))">
                                <img :src="url" class="max-w-full max-h-[80vh] rounded-2xl shadow-2xl object-contain">
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Thumbnails index -->
            <div class="mt-8 flex space-x-2" x-show="allMedia.length > 1">
                <template x-for="(url, index) in allMedia" :key="'thumb' + index">
                    <button @click="activeThumb = index" 
                            :class="activeThumb === index ? 'bg-white w-8' : 'bg-white/30 w-2 hover:bg-white/50'"
                            class="h-2 rounded-full transition-all duration-300"></button>
                </template>
            </div>
        </div>
    </template>

    <!-- File Preview Lightbox -->
    <template x-teleport="body">
        <div x-show="filePreview" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] bg-black/95 flex flex-col items-center justify-center p-4 lg:p-8"
             @keydown.escape.window="filePreview = false">
            
            <div class="relative w-full h-full max-w-6xl flex flex-col bg-white dark:bg-slate-900 rounded-lg shadow-2xl overflow-hidden" @click.away="filePreview = false">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                    <h3 class="text-lg font-bold text-slate-700 dark:text-slate-200 truncate" x-text="filePreviewName"></h3>
                    <div class="flex items-center gap-2">
                        <a :href="filePreviewUrl" download class="p-2 text-slate-500 hover:text-emerald-500 transition-colors" title="Download">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        </a>
                        <button @click="filePreview = false" class="p-2 text-slate-500 hover:text-red-500 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>
                
                <!-- Iframe -->
                <div class="flex-1 w-full h-full bg-slate-100 dark:bg-black/50 relative">
                    <iframe :src="filePreviewUrl" class="w-full h-full absolute inset-0" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </template>

    @php
        $headerMedia = $blog->detail_header_media;
        $isVideo = $blog->isVideo($headerMedia);
        $mediaUrl = $blog->getMediaUrl($headerMedia) ?? $blog->getDefaultMediaUrl();
    @endphp

    @if($mediaUrl)
        <style>
            @keyframes vertical-scroll {
                0% { object-position: center 0%; }
                50% { object-position: center 100%; }
                100% { object-position: center 0%; }
            }
            .animate-vertical-scroll {
                animation: vertical-scroll 45s ease-in-out infinite;
                will-change: object-position;
                opacity: 0.5;
            }
        </style>
        <div class="w-full h-[75px] lg:h-[125px] relative overflow-hidden bg-slate-900 border-b border-white/10 shadow-2xl group">
            @if($isVideo && $headerMedia)
                <video src="{{ $mediaUrl }}" class="w-full h-full object-cover blur-md animate-vertical-scroll" autoplay loop muted playsinline></video>
            @else
                <img src="{{ $mediaUrl }}" class="w-full h-full object-cover blur-md animate-vertical-scroll" alt="{{ $blog->title }}">
            @endif
            <div class="absolute inset-0 bg-black/40 dark:bg-black/60 backdrop-blur-sm"></div>
        </div>
    @endif

    <article class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-16 {{ $headerMedia ? 'pt-12' : 'pt-32' }}">
        <!-- Meta Header -->
        <div class="mb-12">
            <div class="flex flex-wrap items-center gap-2 mb-6 text-xs font-bold uppercase tracking-widest text-slate-500">
                <a href="{{ route('blog.index') }}" class="hover:text-indigo-600 transition-colors">Blogs</a>
                
                @if($blog->categories->count() > 0)
                    @php
                        $category = $blog->categories->first();
                        $breadcrumbs = [];
                        $curr = $category;
                        while($curr) {
                            array_unshift($breadcrumbs, $curr);
                            $curr = $curr->parent;
                        }
                    @endphp
                    
                    @foreach($breadcrumbs as $crumb)
                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        <a href="{{ route('blog.category', $crumb->slug) }}" class="hover:text-indigo-600 transition-colors {{ $loop->last ? 'text-indigo-600' : '' }}">
                            {{ $crumb->title }}
                        </a>
                    @endforeach
                @else
                    <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    <span>Blog</span>
                @endif
            </div>
            
            <h1 class="text-4xl md:text-6xl font-black mb-4 tracking-tight leading-tight text-slate-900 dark:text-white">
                {{ $blog->title }}
            </h1>

            <div class="flex items-center gap-3 mb-4 text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
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

            <div class="flex flex-wrap gap-2 mb-8">
                @foreach($blog->categories as $category)
                    <a href="{{ route('blog.category', $category->slug) }}" 
                       class="px-4 py-1.5 bg-slate-100 dark:bg-[#2a2b3c] hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 dark:hover:text-white text-slate-600 dark:text-slate-400 rounded-full text-xs font-bold transition-all duration-300">
                        {{ $category->title }}
                    </a>
                @endforeach
            </div>

        </div>

        <!-- Main Content -->
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

        <!-- Attachments Section -->
        @if($allAttachments->count() > 0)
            <div class="mt-24 pt-16 border-t border-slate-100 dark:border-slate-800">
                
                <!-- Media Gallery -->
                @if($mediaFiles->count() > 0)
                    <div class="mb-16">
                        <h3 class="text-2xl font-black mb-8 tracking-tight flex items-center gap-3">
                            <span class="bg-indigo-600 w-1.5 h-8 rounded-full"></span>
                            Media Gallery
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-6">
                            @foreach($mediaFiles as $index => $file)
                                @php
                                    $thumbUrl = $file->thumbnail_url ?? $this->blog->getThumbnailUrl($file->path);
                                    $isVideo = $file->type === 'video' || $this->blog->isVideo($file->path);
                                    $ext = $file->extension ? strtoupper($file->extension) : 'FILE';
                                    $size = $file->human_size !== '0 B' ? $file->human_size : '';
                                    
                                    $hasAlt = !empty($file->alt);
                                    $displayText = $hasAlt ? $file->alt : $file->name;
                                @endphp
                                
                                <div class="relative group aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-[#2a2b3c] border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300 cursor-zoom-in"
                                     @click="lightbox = true; activeThumb = {{ $index }}">
                                    @if($thumbUrl)
                                        <img src="{{ $thumbUrl }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="{{ $displayText }}">
                                        @if($isVideo)
                                            <div class="absolute inset-0 flex items-center justify-center bg-black/10 group-hover:bg-black/30 transition-colors">
                                                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 group-hover:scale-110 transition-transform">
                                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
                                                </div>
                                            </div>
                                        @endif
                                    @elseif($isVideo)
                                        <div class="w-full h-full flex items-center justify-center bg-slate-800 text-slate-500">
                                            <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
                                        </div>
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-slate-50 dark:bg-slate-800 p-8">
                                            <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                    @endif

                                    <!-- Bottom Info -->
                                    <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/90 via-black/60 to-transparent translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                                        <p class="text-white text-xs font-semibold truncate">{{ \Illuminate\Support\Str::limit($displayText, 20) }}</p>
                                        <p class="text-white/70 text-[10px]">{{ $size }}</p>
                                    </div>
                                    
                                    <!-- Ext Badge -->
                                    <div class="absolute top-2 left-2 px-1.5 py-0.5 rounded text-[9px] font-bold bg-black/50 text-white backdrop-blur-sm border border-white/10 group-hover:bg-indigo-600 group-hover:border-indigo-500 transition-colors">
                                        {{ $ext }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Other Files -->
                @if($otherFiles->count() > 0)
                    <div>
                        <h3 class="text-2xl font-black mb-8 tracking-tight flex items-center gap-3">
                            <span class="bg-emerald-500 w-1.5 h-8 rounded-full"></span>
                            Other Files
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($otherFiles as $file)
                                @php
                                    $hasAlt = !empty($file->alt);
                                    $displayText = $hasAlt ? $file->alt : $file->name;
                                    $ext = strtolower($file->extension);
                                    $baseIconPath = '/assets/icons/colorful-icons/';
                                    
                                    // Direct extension match (e.g. pdf.svg, doc.svg)
                                    $iconUrl = url($baseIconPath . $ext . '.svg');
                                    // Fallback icon
                                    $fallbackIcon = url($baseIconPath . 'file.svg');
                                    
                                    $fileUrl = $file->url ?? Storage::disk('attachments')->url($file->path);
                                @endphp
                                <!-- Container Link (Removed href from main container to prevent conflict) -->
                                <div class="group flex items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-[#2a2b3c] border border-slate-100 dark:border-slate-700 hover:border-emerald-500/30 hover:shadow-lg hover:shadow-emerald-500/5 transition-all duration-300">
                                    <div class="w-12 h-12 flex-shrink-0 flex items-center justify-center bg-white dark:bg-slate-800 rounded-lg p-2 shadow-sm group-hover:scale-110 transition-transform">
                                        <img src="{{ $iconUrl }}" 
                                             class="w-full h-full object-contain" 
                                             alt="{{ $ext }}"
                                             onerror="this.onerror=null; this.src='{{ $fallbackIcon }}';">
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-slate-700 dark:text-slate-200 text-sm truncate group-hover:text-emerald-500 transition-colors">
                                            {{ $displayText }}
                                        </h4>
                                        <div class="flex items-center gap-2 text-xs text-slate-400 mt-1">
                                            <span class="uppercase font-semibold bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400 px-1 py-0.5 rounded-[4px]">
                                                {{ strtoupper($ext) }}
                                            </span>
                                            <span>&bull;</span>
                                            <span>{{ $file->human_size }}</span>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                        @if(in_array($ext, ['pdf', 'txt', 'json', 'xml', 'md', 'csv', 'mp3', 'wav']))
                                            <button @click="filePreview = true; filePreviewUrl = '{{ $fileUrl }}'; filePreviewName = {{ \Illuminate\Support\Js::from($displayText) }}" 
                                               class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 dark:bg-slate-700 text-slate-500 hover:bg-indigo-500 hover:text-white transition-all transform hover:scale-110" 
                                               title="Preview">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                        @endif
                                        
                                        <a href="{{ $fileUrl }}" download 
                                           class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 dark:bg-slate-700 text-slate-500 hover:bg-emerald-500 hover:text-white transition-all transform hover:scale-110"
                                           title="Download">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </article>
</div>