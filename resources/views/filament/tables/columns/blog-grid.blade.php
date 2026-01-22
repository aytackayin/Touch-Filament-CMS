@php
    $record = $getRecord();
    // Veritabanında ters sırada tutulduğu için array'i tersine çeviriyoruz
    $attachments = is_array($record->attachments) ? array_reverse(array_values($record->attachments)) : [];
    $imageUrl = null;
    $disk = \Illuminate\Support\Facades\Storage::disk('attachments');

    foreach ($attachments as $attachment) {
        if (!is_string($attachment))
            continue;

        $ext = strtolower(pathinfo($attachment, PATHINFO_EXTENSION));

        // Durum 1: Resim ise
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
            $filename = basename($attachment);
            $thumbPath = "blogs/{$record->id}/images/thumbs/{$filename}";

            if ($disk->exists($thumbPath)) {
                $imageUrl = $disk->url($thumbPath);
            } elseif ($disk->exists($attachment)) {
                $imageUrl = $disk->url($attachment);
            }
        }
        // Durum 2: Video ise
        elseif (in_array($ext, ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mkv', 'webm'])) {
            $videoName = pathinfo($attachment, PATHINFO_FILENAME);
            $videoThumbPath = "blogs/{$record->id}/videos/thumbs/{$videoName}.jpg";

            if ($disk->exists($videoThumbPath)) {
                $imageUrl = $disk->url($videoThumbPath);
            }
        }

        if ($imageUrl) {
            break;
        }
    }

    $fallbackUrl = url('/assets/icons/colorful-icons/grid-blog.svg');
    if (!$imageUrl) {
        $imageUrl = $fallbackUrl;
    }

    $name = $record->title;
@endphp

<div class="blog-card">
    <img src="{{ $imageUrl }}" alt="{{ $name }}" class="blog-bg {{ $imageUrl === $fallbackUrl ? 'is-icon' : '' }}"
        onerror="this.src='{{ $fallbackUrl }}'; this.classList.add('is-icon')">

    @if(!$record->is_published)
        <div class="blog-status-badge">
            Taslak
        </div>
    @endif

    <div class="blog-overlay bg-white dark:bg-black">
        <div class="blog-name" title="{{ $name }}">
            {{ $name }}
        </div>

        <div class="blog-info">
            <span>{{ $record->created_at->format('d.m.Y') }}</span>
            <span>{{ $record->user?->name }}</span>
        </div>
    </div>
</div>

<style>
    /* === KART ANA TAŞIYICI === */
    body .blogs-grid .fi-ta-record {
        position: relative !important;
        padding: 0 !important;
        overflow: hidden !important;
        border-radius: 14px !important;
    }

    /* Filament iç paddingleri tamamen kapat */
    body .blogs-grid .fi-ta-record-content-ctn,
    body .blogs-grid .fi-ta-record-content {
        padding: 0 !important;
    }

    /* === BACKGROUND IMAGE === */
    .blog-card {
        position: relative;
        width: 100%;
        height: 100%;
        min-height: 200px;
    }

    .blog-bg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
    }

    /* ikon fallback */
    .blog-bg.is-icon {
        object-fit: contain;
        padding: 40px;
        opacity: 0.85;
    }

    /* === ALT OVERLAY (Glassmorphism) === */
    .blog-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 5;
        padding: 12px;
        background-color: rgba(255, 255, 255, 0.5);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .dark .blog-overlay {
        background-color: rgba(0, 0, 0, 0.5);
    }

    /* === TEXT === */
    .blog-name {
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: center;
    }

    .blog-info {
        margin-top: 4px;
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        opacity: 0.8;
    }

    /* Status Badge */
    .blog-status-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 10;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 600;
        background: rgba(239, 68, 68, 0.9);
        color: white;
    }

    /* === CHECKBOX === */
    body .blogs-grid .fi-ta-record>input[type="checkbox"] {
        position: absolute !important;
        top: 12px !important;
        right: 12px !important;
        left: auto !important;
        bottom: auto !important;
        z-index: 10 !important;
        width: 20px !important;
        height: 20px !important;
        border-radius: 6px !important;
        margin: 0 !important;
        cursor: pointer !important;
    }

    /* === ACTIONS (HOVER) === */
    body .blogs-grid .fi-ta-record .fi-ta-actions {
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

    body .blogs-grid .fi-ta-record:hover .fi-ta-actions {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, 0);
        pointer-events: auto;
    }
</style>