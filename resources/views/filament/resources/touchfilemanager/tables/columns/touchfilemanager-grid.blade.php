@php
    $record = $getRecord();
    $isFolder = $record->is_folder;
    $isUp = $record->id === 0;

    $iconConfig = config('touch-file-manager.icon_paths');
    $basePath = $iconConfig['base'] ?? '/assets/icons/colorful-icons/';
    $folderIcon = $basePath . 'grid-' . ($iconConfig['folder'] ?? 'folder.svg');
    $fileIcon = $basePath . 'grid-' . ($iconConfig['file'] ?? 'file.svg');

    if ($isUp) {
        $imageUrl = url($basePath . 'grid-open-folder.svg');
        $name = __('touch_file_manager.label.up');
        $fallbackUrl = '';
    } else {
        // Determine Image URL
        if ($record->thumbnail_path) {
            $imageUrl = \Illuminate\Support\Facades\Storage::disk('attachments')->url($record->thumbnail_path);
        } else {
            if ($isFolder) {
                $imageUrl = url($folderIcon);
            } else {
                // Check exclusions: Image, Video, Audio
                $isMedia = in_array($record->type, ['image', 'video'])
                    || \Illuminate\Support\Str::startsWith($record->mime_type ?? '', 'audio/');

                if ($isMedia) {
                    $imageUrl = url($fileIcon);
                } else {
                    $ext = strtolower($record->extension);
                    $imageUrl = $ext
                        ? url($basePath . "grid-{$ext}.svg")
                        : url($fileIcon);
                }
            }
        }

        // Show Alt if available, otherwise Name
        $name = (!empty($record->alt)) ? $record->alt : $record->name;

        // Determine Fallback URL for Error
        $fallbackUrl = $isFolder ? url($folderIcon) : url($fileIcon);
    }
@endphp

<div class="touch-file-card" @if(request()->query('iframe') && !$isUp && !$isFolder) x-on:click.stop="
    window.parent.postMessage({ 
        mceAction: 'insert', 
        content: '{{ parse_url(\Illuminate\Support\Facades\Storage::disk('attachments')->url($record->path), PHP_URL_PATH) }}', 
        alt: '{{ str_replace(["\r", "\n", "'"], ["", "", "\\'"], $record->alt ?? '') }}' 
    }, '*');
" style="cursor: pointer;" @endif>
    <img src="{{ $imageUrl }}" alt="{{ $name }}" class="touch-file-bg {{ $isUp ? 'is-icon' : '' }}"
        onerror="this.src='{{ $fallbackUrl }}'; this.classList.add('is-icon')">

    @if(!$isUp && !$isFolder)
        @php
            $typeColor = match ($record->type) {
                'image' => '#22c55e',       // green-500
                'video' => '#0ea5e9',       // sky-500
                'document' => '#6366f1',    // indigo-500
                'archive' => '#f59e0b',     // amber-500
                'spreadsheet' => '#10b981', // emerald-500
                'presentation' => '#ef4444', // red-500
                default => '#6b7280',        // gray-500
            };
        @endphp
        <div class="touch-file-type-badge" style="background-color: {{ $typeColor }};">
            {{ __('touch_file_manager.label.types.' . ($record->type ?? 'other')) }}
        </div>
    @endif

    @if(!$isUp && (str_contains($record->type ?? '', 'video') || str_contains($record->mime_type ?? '', 'video')))
        <div
            style="position: absolute !important; inset: 0 !important; display: flex !important; align-items: center !important; justify-content: center !important; z-index: 1 !important; pointer-events: none !important;">
            <div
                style="width: 48px !important; height: 48px !important; background: rgba(255, 255, 255, 0.3) !important; backdrop-filter: blur(8px) !important; -webkit-backdrop-filter: blur(8px) !important; border-radius: 9999px !important; display: flex !important; align-items: center !important; justify-content: center !important; border: 1px solid rgba(255, 255, 255, 0.2) !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;">
                <svg width="24" height="24" fill="white" viewBox="0 0 24 24"
                    style="margin-left: 2px !important; display: block !important;">
                    <path d="M8 5v14l11-7z"></path>
                </svg>
            </div>
        </div>
    @endif

    <div class="touch-file-{{ $isFolder ? 'folder' : 'overlay bg-white dark:bg-black' }}">
        <div class="touch-file-name" title="{{ $name }}">
            {{ $name }}
        </div>

        @if(!$isFolder)
            <div class="touch-file-info">
                <span>{{ $record->human_size }}</span>
                <span>{{ strtoupper($record->extension) }}</span>
            </div>
        @else
            <div class="touch-file-info">
            </div>
        @endif
    </div>
</div>

<style>
    /* === KART ANA TAŞIYICI === */
    body .touch-file-manager-grid .fi-ta-record {
        position: relative !important;
        padding: 0 !important;
        overflow: hidden !important;
        border-radius: 14px !important;
    }

    /* Filament iç paddingleri tamamen kapat */
    body .touch-file-manager-grid .fi-ta-record-content-ctn,
    body .touch-file-manager-grid .fi-ta-record-content {
        padding: 0 !important;
    }

    /* === BACKGROUND IMAGE === */
    .touch-file-card {
        position: relative;
        width: 100%;
        height: 100%;
        min-height: 180px;
    }

    .touch-file-bg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
    }

    /* ikon fallback */
    .touch-file-bg.is-icon {
        object-fit: contain;
        padding: 32px;
        opacity: 0.85;
    }

    /* === ALT OVERLAY (Glassmorphism) === */
    .touch-file-overlay,
    .touch-file-folder {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 5;
        padding: 12px;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    /* Aydınlık mod */
    .touch-file-overlay,
    .touch-file-folder {
        background-color: rgba(255, 255, 255, 0.5);
    }

    /* Karanlık mod */
    .dark .touch-file-overlay,
    .dark .touch-file-folder {
        background-color: rgba(0, 0, 0, 0.5);
    }

    /* === TEXT === */
    .touch-file-name {
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: center;
    }

    .touch-file-info {
        margin-top: 4px;
        display: flex;
        justify-content: center;
        gap: 10px;
        font-size: 11px;
        opacity: 0.8;
    }

    /* === TYPE BADGE === */
    .touch-file-type-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 20;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 700;
        color: white;
        text-transform: uppercase;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        pointer-events: none;
    }

    /* === CHECKBOX === */
    body .touch-file-manager-grid .fi-ta-record>input[type="checkbox"] {
        position: absolute !important;
        top: 12px !important;
        right: 12px !important;
        left: auto !important;
        bottom: auto !important;
        z-index: 20 !important;
        width: 20px !important;
        height: 20px !important;
        border-radius: 6px !important;
        margin: 0 !important;
        cursor: pointer !important;
        border: 1px solid white !important;
        box-shadow: 0 0 8px rgba(0, 0, 0, 0.2) !important;
        background-color: white !important;
        transition: all 0.2s ease !important;
    }

    /* Karanlık Modda Checkbox Görünürlüğü */
    .dark body .touch-file-manager-grid .fi-ta-record>input[type="checkbox"] {
        border-color: #727272ff !important;
        background-color: #1e1e2d !important;
        box-shadow: 0 0 12px rgba(0, 0, 0, 0.6) !important;
    }

    body .touch-file-manager-grid .fi-ta-record>input[type="checkbox"]:checked {
        background-color: #4f46e5 !important;
        border-color: #4f46e5 !important;
    }

    /* === ACTIONS (HOVER) === */
    body .touch-file-manager-grid .fi-ta-record .fi-ta-actions {
        position: absolute !important;
        bottom: 12px !important;
        left: 50% !important;
        transform: translate(-50%, 10px);
        z-index: 30 !important;

        opacity: 0;
        visibility: hidden;
        pointer-events: none;

        background: rgba(0, 0, 0, 0.35);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border-radius: 10px;
        padding: 6px 10px;
        transition: all .3s ease;
    }

    body .touch-file-manager-grid .fi-ta-record:hover .fi-ta-actions {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, 0);
        pointer-events: auto;
    }
</style>