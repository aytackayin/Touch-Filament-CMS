<div class="p-3 bg-white dark:bg-gray-800 rounded-b-xl border-x border-b border-gray-100 dark:border-gray-700">
    <div class="flex flex-col text-center sm:text-left">
        <span class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate" title="{{ $getRecord()->name }}">
            {{ $getRecord()->name }}
        </span>
        <div class="flex items-center justify-between mt-1">
            <span class="text-[10px] text-gray-400">
                {{ $getRecord()->is_folder ? 'Folder' : $getRecord()->human_size }}
            </span>
            <span class="text-[10px] text-gray-500 uppercase font-medium">
                {{ $getRecord()->is_folder ? '' : $getRecord()->extension }}
            </span>
        </div>
    </div>
</div>

<style>
    /* 
       Filament 4 GRID Checkbox Kesin Çözüm 
       Bu stil her kartın içine basılır ve tüm tabloyu etkiler.
    */

    /* 1. Kayıt satırını (kartın kendisi) relative yap */
    .fi-ta-record {
        position: relative !important;
    }

    /* 2. Grid görünümündeyken checkbox'ı yakala ve sağ üste yapıştır */
    /* Seçicileri hem is-grid-view hem de touch-file-manager-grid için genişletiyoruz */
    .is-grid-view .fi-ta-record .fi-ta-record-checkbox,
    .touch-file-manager-grid .fi-ta-record .fi-ta-record-checkbox,
    .is-grid-view .fi-ta-record input[type="checkbox"],
    .touch-file-manager-grid .fi-ta-record input[type="checkbox"] {
        position: absolute !important;
        top: 10px !important;
        right: 10px !important;
        left: auto !important;
        bottom: auto !important;
        z-index: 30 !important;
        /* Modalların altında kalması için düşürüldü */
        margin: 0 !important;
        width: 22px !important;
        height: 22px !important;

        /* Premium görünüm için stiller */
        background-color: rgba(255, 255, 255, 0.9) !important;
        border: 2px solid white !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;

        /* Flex akışından tamamen kopar */
        float: none !important;
        display: block !important;
    }

    /* Karanlık mod uyumu */
    .dark .is-grid-view .fi-ta-record .fi-ta-record-checkbox,
    .dark .touch-file-manager-grid .fi-ta-record .fi-ta-record-checkbox {
        background-color: rgba(30, 41, 59, 0.8) !important;
        border-color: #475569 !important;
    }

    /* Checkbox'ın altındaki içeriğin (resim vb.) checkbox'ın altında kalmasını sağla */
    .is-grid-view .fi-ta-record-content-ctn {
        z-index: 10 !important;
        position: relative !important;
    }
</style>