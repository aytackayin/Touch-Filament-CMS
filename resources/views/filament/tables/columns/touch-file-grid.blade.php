<div
    class="relative group rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow duration-300 h-full flex flex-col touch-file-card">

    {{-- Önizleme Alanı --}}
    <div
        class="h-32 w-full bg-gray-50 dark:bg-gray-900 flex items-center justify-center overflow-hidden relative border-b border-gray-100 dark:border-gray-700">
        @if($getRecord()->is_folder)
            <img src="{{ url('/images/icons/folder.png') }}" class="h-16 w-16 object-contain mx-auto drop-shadow-sm"
                alt="Folder">
        @else
            @php
                $thumbnail = $getRecord()->thumbnail_path;
                $isImage = Str::startsWith($getRecord()->mime_type, 'image/');
            @endphp
            @if($isImage && $thumbnail)
                <img src="{{ url('storage/' . $thumbnail) }}" class="w-full h-full object-cover"
                    onerror="this.src='{{ url('/images/icons/file.png') }}'; this.className='h-12 w-12 object-contain mx-auto opacity-50';"
                    alt="{{ $getRecord()->name }}">
            @else
                <div class="flex flex-col items-center">
                    <img src="{{ url('/images/icons/file.png') }}" class="h-12 w-12 object-contain mx-auto opacity-80"
                        alt="File">
                </div>
            @endif
        @endif
    </div>

    {{-- Dosya Bilgileri --}}
    <div class="p-3 flex flex-col flex-grow justify-between min-h-[80px]">
        <div>
            <span class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate block w-full mb-1"
                title="{{ $getRecord()->name }}">
                {{ $getRecord()->name }}
            </span>

            <div class="flex items-center space-x-2">
                <span
                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                    {{ $getRecord()->is_folder ? 'Folder' : strtoupper($getRecord()->extension ?? 'FILE') }}
                </span>

                @if(!$getRecord()->is_folder)
                    <span class="text-[10px] text-gray-400 dark:text-gray-500">
                        {{ $getRecord()->human_size }}
                    </span>
                @endif
            </div>
        </div>

        <div
            class="text-[10px] text-gray-400 dark:text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
            {{ $getRecord()->created_at->format('d.m.Y') }}
        </div>
    </div>
</div>

<style>
    /* 
       Grid öğelerinin düzgün sıralanması için 
    */
    .touch-file-manager-grid .fi-ta-content-grid-item {
        position: relative !important;
        display: flex !important;
        flex-direction: column !important;
    }

    /* Checkbox'ı Sağ Üste Taşı */
    .touch-file-manager-grid .fi-ta-content-grid-item .fi-ta-selection-cell {
        position: absolute !important;
        top: 8px !important;
        right: 8px !important;
        left: auto !important;
        z-index: 30 !important;
        padding: 0 !important;
        margin: 0 !important;
        background: white !important;
        border-radius: 4px !important;
        width: 24px !important;
        height: 24px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #e5e7eb !important;
    }

    .dark .touch-file-manager-grid .fi-ta-content-grid-item .fi-ta-selection-cell {
        background: #1f2937 !important;
        border-color: #374151 !important;
    }

    /* Checkbox'ın kendisini küçültelim */
    .touch-file-manager-grid .fi-ta-content-grid-item .fi-ta-selection-cell input {
        margin: 0 !important;
        cursor: pointer !important;
    }

    /* Görsellerin devleşmesini engelle */
    .touch-file-card img {
        max-width: 100% !important;
    }
</style>