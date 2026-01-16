@php
    $record = $getRecord();
    $isFolder = $record->is_folder;

    $imageUrl = $record->thumbnail_path
        ? \Illuminate\Support\Facades\Storage::disk('attachments')->url($record->thumbnail_path)
        : ($isFolder
            ? url('/images/icons/grid-folder.png')
            : url('/images/icons/file.png'));
@endphp

<div class="touch-file-card">
    <img src="{{ $imageUrl }}" alt="{{ $record->name }}" class="touch-file-bg"
        onerror="this.src='{{ $isFolder }}'; this.classList.add('is-icon')">

    <div class="touch-file-overlay">
        <div class="touch-file-name" title="{{ $record->name }}">
            {{ $record->name }}
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

    /* === ALT OVERLAY === */
    .touch-file-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 5;

        padding: 12px;
        background-color: rgba(0, 0, 0, 0.5);

        color: #fff;
    }

    /* === TEXT === */
    .touch-file-name {
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .touch-file-info {
        margin-top: 4px;
        display: flex;
        gap: 10px;
        font-size: 11px;
        opacity: 0.8;
    }

    /* === CHECKBOX === */
    body .touch-file-manager-grid .fi-ta-record>input[type="checkbox"] {
        position: absolute !important;
        top: 12px !important;
        right: 12px !important;
        left: auto !important;
        bottom: auto !important;
        z-index: 10 !important;
        width: 20px !important;
        height: 20px !important;
        border-radius: 6px !important;
        background-color: rgba(0, 0, 0, 0.6) !important;
        border: 2px solid rgba(255, 255, 255, 0.5) !important;
        backdrop-filter: blur(10px) !important;
        margin: 0 !important;
        cursor: pointer !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5) !important;
    }

    body .touch-file-manager-grid .fi-ta-record .fi-ta-actions .fi-text-color-900 {
        --text: #fafafa;
    }

    body .touch-file-manager-grid .fi-ta-record .fi-ta-actions .fi-text-color-700 {
        --text: #ff0000;
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