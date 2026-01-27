@props(['category', 'categoryId', 'activePath', 'level' => 0])

@php 
                    $isChildActive = collect($activePath)->contains(fn($id) => collect($category->allChildren->pluck('id'))->contains($id));
    $isActive = $categoryId == $category->id || $isChildActive;
@endphp

<div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }" class="space-y-2">
    <div class="flex items-center group/cat">
        <button wire:click="$set('categoryId', {{ $category->id }})"
            class="flex-1 flex items-center justify-between px-4 py-2.5 rounded-xl transition-all border-l-4 {{ $level === 0 ? 'text-sm font-bold' : 'text-xs font-bold' }} {{ $isActive ? 'bg-indigo-600/10 border-indigo-600 text-indigo-600 dark:bg-indigo-600/20 shadow-sm' : 'border-transparent text-slate-500 hover:bg-slate-100 dark:hover:bg-[#222330]' }}">
            <span>{{ $category->title }}</span>
            <span
                class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $isActive ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">
                {{ $category->total_blogs_count }}
            </span>
        </button>
        @if($category->children->count() > 0)
            <button @click="open = !open" class="p-2 text-slate-400 hover:text-indigo-500 transition-colors">
                <svg class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180 text-indigo-500' : ''"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        @endif
    </div>

    @if($category->children->count() > 0)
        <div x-show="open" x-collapse class="pl-5 ml-4 border-l-2 border-slate-100 dark:border-slate-800 space-y-1 mt-1">
            @foreach($category->children as $child)
                @include('frontend.pages.blog.components.category-item', ['category' => $child, 'categoryId' => $categoryId, 'activePath' => $activePath, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>