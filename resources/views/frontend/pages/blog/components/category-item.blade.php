@props(['category', 'categorySlug', 'activePath', 'level' => 0])

@php 
            $isChildActive = collect($activePath)->contains(fn($id) => collect($category->allChildren->pluck('id'))->contains($id));
    $isActive = $categorySlug == $category->slug || $isChildActive;
@endphp

<div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }"
    x-on:category-opened.window="if($event.detail.parentId === {{ $category->parent_id ?? 'null' }} && $event.detail.id !== {{ $category->id }}) open = false"
    class="space-y-2">
    <div class="flex items-center group/cat">
        <a href="{{ route('blog.category', $category->slug) }}" wire:navigate
            class="flex-1 flex items-center justify-between px-4 py-2.5 rounded-xl transition-all border-l-4 {{ $level === 0 ? 'text-sm font-bold' : 'text-xs font-bold' }} {{ $isActive ? 'bg-indigo-600/10 border-indigo-600 text-indigo-600 dark:bg-indigo-600/20 shadow-sm' : 'border-transparent text-slate-500 hover:bg-slate-100 dark:hover:bg-[#222330]' }}">
            <span>{{ $category->title }}</span>
            <span
                class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $isActive ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">
                {{ $category->total_blogs_count }}
            </span>
        </a>
        @if($category->children->count() > 0)
            <button
                @click="open = !open; if(open) $dispatch('category-opened', { id: {{ $category->id }}, parentId: {{ $category->parent_id ?? 'null' }} })"
                class="p-2 text-slate-400 hover:text-indigo-500 transition-colors">
                <svg class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180 text-indigo-500' : ''"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        @endif
    </div>

    @if($category->children->count() > 0)
        <div x-show="open" x-collapse class="pl-4 ml-3 space-y-1 mt-1 relative">
            <!-- Vertical Tree Line -->
            <div class="absolute left-0 top-0 bottom-2 w-px bg-slate-200 dark:bg-slate-700"></div>

            @foreach($category->children as $child)
                <div class="relative">
                    <!-- Horizontal Connector -->
                    <div class="absolute -left-4 top-5 w-3 h-px bg-slate-200 dark:bg-slate-700"></div>
                    @include('frontend.pages.blog.components.category-item', ['category' => $child, 'categorySlug' => $categorySlug, 'activePath' => $activePath, 'level' => $level + 1])
                </div>
            @endforeach
        </div>
    @endif
</div>