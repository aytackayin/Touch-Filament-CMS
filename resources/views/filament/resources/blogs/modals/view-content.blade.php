<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap');

    .blog-modal-container {
        font-family: 'Outfit', sans-serif !important;
        line-height: 1.6 !important;
    }

    /* Hero Image */
    .blog-modal-hero {
        width: 100% !important;
        height: 380px !important;
        object-fit: cover !important;
        border-radius: 20px !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    }

    /* Badges & Meta */
    .meta-stack {
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
        margin-bottom: 15px !important;
        /* Reduced margin before content */
    }

    .meta-line {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        color: #64748b !important;
    }

    .dark .meta-line {
        color: #94a3b8 !important;
    }

    .meta-label {
        min-width: 100px !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        font-size: 11px !important;
        letter-spacing: 0.05em !important;
    }

    .badge-list {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
    }

    .custom-badge {
        padding: 4px 12px !important;
        border-radius: 99px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    .dark .custom-badge {
        background: #1e293b !important;
        color: #cbd5e1 !important;
        border-color: rgba(255, 255, 255, 0.05) !important;
    }

    .category-badge {
        background: #eff6ff !important;
        color: #2563eb !important;
        border-color: #dbeafe !important;
    }

    .dark .category-badge {
        background: rgba(37, 99, 235, 0.1) !important;
        color: #60a5fa !important;
        border-color: rgba(37, 99, 235, 0.2) !important;
    }

    /* Content Area */
    .blog-content-rich {
        background: #ffffff !important;
        border-radius: 24px !important;
        padding: 2rem !important;
        margin: 0 0 15px 0 !important;
        /* Reduced bottom margin */
        border: 1px solid #f1f5f9 !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
    }

    .dark .blog-content-rich {
        background: #0f172a !important;
        border-color: #1e293b !important;
        box-shadow: none !important;
    }

    /* Attachments Grid */
    .attachment-card {
        display: flex !important;
        align-items: center !important;
        gap: 16px !important;
        padding: 12px !important;
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 16px !important;
        transition: all 0.3s ease !important;
        text-decoration: none !important;
        color: inherit !important;
    }

    .attachment-card:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        border-color: #cbd5e1 !important;
    }

    .dark .attachment-card {
        background: #1e293b !important;
        border-color: #334155 !important;
    }

    .attachment-thumb-wrap {
        width: 70px !important;
        height: 70px !important;
        flex-shrink: 0 !important;
        border-radius: 10px !important;
        overflow: hidden !important;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .dark .attachment-thumb-wrap {
        background: #0f172a !important;
        border-color: #334155 !important;
    }

    .attachment-info .file-name {
        font-weight: 700 !important;
        font-size: 13px !important;
        color: #1e293b !important;
        margin-bottom: 2px !important;
        display: block !important;
        word-break: break-all !important;
    }

    .dark .attachment-info .file-name {
        color: #f1f5f9 !important;
    }

    /* Footer Info */
    .blog-modal-footer {
        background: #f8fafc !important;
        border-radius: 20px !important;
        padding: 1.5rem !important;
        margin-top: 2rem !important;
    }

    .dark .blog-modal-footer {
        background: #1e293b !important;
    }

    .footer-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) !important;
        gap: 20px !important;
        margin-bottom: 1.5rem !important;
    }

    .footer-item label {
        display: block !important;
        font-size: 10px !important;
        text-transform: uppercase !important;
        font-weight: 800 !important;
        color: #94a3b8 !important;
        letter-spacing: 0.05em !important;
        margin-bottom: 4px !important;
    }

    .footer-item span {
        font-size: 13px !important;
        font-weight: 600 !important;
        color: #334155 !important;
    }

    .dark .footer-item span {
        color: #cbd5e1 !important;
    }

    /* Status Styling */
    .status-wrapper {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 8px 16px !important;
        border-radius: 12px !important;
        background: #ffffff !important;
        font-weight: 900 !important;
        letter-spacing: 0.05em !important;
    }

    .dark .status-wrapper {
        background: #0f172a !important;
    }

    .status-published {
        color: #16a34a !important;
        border: 1px solid #dcfce7 !important;
    }

    .dark .status-published {
        color: #4ade80 !important;
        border-color: rgba(34, 197, 94, 0.2) !important;
    }

    .status-draft {
        color: #dc2626 !important;
        border: 1px solid #fee2e2 !important;
    }

    .dark .status-draft {
        color: #f87171 !important;
        border-color: rgba(239, 68, 68, 0.2) !important;
    }

    .status-icon {
        width: 22px !important;
        height: 22px !important;
        flex-shrink: 0 !important;
    }

    .status-text {
        font-size: 12px !important;
    }

    .section-title {
        font-size: 18px !important;
        font-weight: 800 !important;
        color: #1e293b !important;
        margin-bottom: 1rem !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }

    .dark .section-title {
        color: #f1f5f9 !important;
    }
</style>

<div class="blog-modal-container">
    {{-- Hero Header --}}
    @if($record->cover_image)
        <img src="{{ $record->getThumbnailPath() }}" alt="{{ $record->title }}" class="blog-modal-hero">
    @endif

    {{-- Top Meta Info --}}
    <div class="meta-stack">
        @if($record->slug)
            <div class="meta-line">
                <span class="meta-label">{{ __('blog.label.slug') }}:</span>
                <span class="font-mono" style="color: #6366f1;">{{ $record->slug }}</span>
            </div>
        @endif

        @if($record->categories->count())
            <div class="meta-line">
                <span class="meta-label">{{ __('blog.label.categories') }}:</span>
                <div class="badge-list">
                    @foreach($record->categories as $category)
                        <div class="custom-badge category-badge">{{ $category->title }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($record->tags && count($record->tags) > 0)
            <div class="meta-line">
                <span class="meta-label">{{ __('blog.label.tags') }}:</span>
                <div class="badge-list">
                    @foreach($record->tags as $tag)
                        <div class="custom-badge">#{{ $tag }}</div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Main Content Card --}}
    <div class="blog-content-rich">
        <div class="prose dark:prose-invert max-w-none">
            {!! $record->content !!}
        </div>
    </div>

    {{-- Attachments Section --}}
    @if($record->attachments && count($record->attachments) > 0)
        <div style="margin: 0 0 2rem 0 !important;">
            <h3 class="section-title">
                <svg style="width: 24px; height: 24px; color: #6366f1;" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.5L8.25 18.75a1.5 1.5 0 1 1-2.12-2.12L15.75 7.5" />
                </svg>
                {{ __('blog.label.attachments') }}
            </h3>
            <div
                style="display: grid !important; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important; gap: 12px !important;">
                @foreach($record->attachments as $attachment)
                    @php
                        $isImage = $record->isImage($attachment);
                        $isVideo = $record->isVideo($attachment);
                        $thumb = $record->getThumbnailUrl($attachment);
                        $fileName = basename($attachment);
                    @endphp
                    <a href="{{ $record->getMediaUrl($attachment) }}" target="_blank" class="attachment-card">
                        <div class="attachment-thumb-wrap">
                            @if(($isImage || $isVideo) && $thumb)
                                <img src="{{ $thumb }}"
                                    style="width: 100% !important; height: 100% !important; object-fit: cover !important;">
                            @else
                                <img src="{{ asset('assets/icons/colorful-icons/file.svg') }}"
                                    style="width: 32px !important; height: 32px !important;">
                            @endif
                        </div>
                        <div class="attachment-info">
                            <span class="file-name" title="{{ $fileName }}">{{ Str::limit($fileName, 25) }}</span>
                            <div class="view-link text-[#6366f1] hover:underline">
                                <svg style="width: 12px; height: 12px;" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                                <span style="font-size: 11px;">{{ __('blog.label.view_file') }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Premium Footer --}}
    <div class="blog-modal-footer">
        <div class="footer-grid">
            <div class="footer-item">
                <label>{{ __('blog.label.created_at') }}</label>
                <span>{{ $record->created_at->format('d M Y, H:i') }}</span>
            </div>

            @if($record->publish_start)
                <div class="footer-item">
                    <label>{{ __('blog.label.publish_start') }}</label>
                    <span>{{ $record->publish_start instanceof \DateTime ? $record->publish_start->format('d M Y, H:i') : $record->publish_start }}</span>
                </div>
            @endif

            @if($record->publish_end)
                <div class="footer-item">
                    <label>{{ __('blog.label.publish_end') }}</label>
                    <span>{{ $record->publish_end instanceof \DateTime ? $record->publish_end->format('d M Y, H:i') : $record->publish_end }}</span>
                </div>
            @endif

            @if($record->user)
                <div class="footer-item">
                    <label>{{ __('blog.label.author') }}</label>
                    <span>{{ $record->user->name }}</span>
                </div>
            @endif

            @if($record->language)
                <div class="footer-item">
                    <label>{{ __('blog.label.language') }}</label>
                    <span>{{ $record->language->name }}</span>
                </div>
            @endif
        </div>

        <div
            style="display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap !important; gap: 1rem !important; border-top: 1px solid rgba(0,0,0,0.05) !important; padding-top: 1.5rem !important;">
            @if($record->editor)
                <div class="footer-item"
                    style="display: flex !important; align-items: center !important; gap: 8px !important;">
                    <label style="margin-bottom: 0 !important;">{{ __('blog.label.last_edited_by') }}:</label>
                    <span style="font-size: 12px !important;">{{ $record->editor->name }}</span>
                </div>
            @endif

            <div class="status-wrapper {{ $record->is_published ? 'status-published' : 'status-draft' }}">
                @if($record->is_published)
                    <svg class="status-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="status-text">{{ __('blog.label.published') }}</span>
                @else
                    <svg class="status-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="status-text">{{ __('blog.label.draft') }}</span>
                @endif
            </div>
        </div>
    </div>
</div>