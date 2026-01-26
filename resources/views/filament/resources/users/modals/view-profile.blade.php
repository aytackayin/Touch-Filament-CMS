<div class="user-profile-modal-container">
    <style>
        .user-profile-modal-container {
            --upm-bg: #ffffff;
            --upm-header: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            --upm-text-main: #111827;
            --upm-text-sub: #6b7280;
            --upm-card-bg: #f3f4f6;
            --upm-border: #e5e7eb;
            --upm-accent: #6366f1;

            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--upm-text-main);
            margin: -24px;
            /* Offset Filament's modal padding */
            overflow: hidden;
            border-radius: 12px;
        }

        .dark .user-profile-modal-container {
            --upm-bg: #0f172a;
            --upm-text-main: #f1f5f9;
            --upm-text-sub: #94a3b8;
            --upm-card-bg: #1e293b;
            --upm-border: #334155;
        }

        .upm-header {
            background: var(--upm-header);
            height: 140px;
            position: relative;
        }

        .upm-avatar-wrapper {
            position: absolute;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }

        .upm-avatar {
            width: 110px;
            height: 110px;
            border-radius: 24px;
            border: 5px solid var(--upm-bg);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            object-fit: cover;
            background: #eee;
        }

        .upm-body {
            padding: 60px 24px 24px;
            background: var(--upm-bg);
            text-align: center;
        }

        .upm-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.025em;
        }

        .upm-subtitle {
            font-size: 0.9rem;
            color: var(--upm-text-sub);
            margin: 4px 0 16px;
        }

        .upm-badges {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .upm-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 99px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--upm-accent);
            text-transform: uppercase;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .upm-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .upm-card {
            background: var(--upm-card-bg);
            border: 1px solid var(--upm-border);
            padding: 16px;
            border-radius: 16px;
            text-align: left;
        }

        .upm-label {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--upm-text-sub);
            text-transform: uppercase;
            display: block;
            margin-bottom: 6px;
        }

        .upm-value {
            font-size: 0.85rem;
            font-weight: 600;
            line-height: 1.4;
        }

        .upm-section-title {
            font-size: 0.85rem;
            font-weight: 800;
            text-align: left;
            margin-bottom: 12px;
            display: block;
            padding-left: 4px;
        }

        .upm-social-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 24px;
        }

        .upm-social-link {
            flex: 1;
            min-width: 120px;
            padding: 10px;
            background: var(--upm-card-bg);
            border: 1px solid var(--upm-border);
            border-radius: 12px;
            text-decoration: none;
            color: var(--upm-text-main);
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .upm-social-link:hover {
            border-color: var(--upm-accent);
            transform: translateY(-2px);
        }

        .upm-footer {
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            color: var(--upm-text-sub);
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--upm-border);
        }
    </style>

    <div class="upm-header">
        <div class="upm-avatar-wrapper">
            <img src="{{ $record->avatar_url ? Storage::disk('attachments')->url($record->avatar_url) : 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random' }}"
                class="upm-avatar" alt="">
        </div>
    </div>

    <div class="upm-body">
        <h2 class="upm-title">{{ $record->name }}</h2>
        <p class="upm-subtitle">{{ $record->email }}</p>

        <div class="upm-badges">
            @foreach($record->roles as $role)
                <span class="upm-badge">{{ $role->name }}</span>
            @endforeach
        </div>

        <div class="upm-grid">
            <div class="upm-card">
                <span class="upm-label">{{ __('user.label.phone') }}</span>
                <span class="upm-value">{{ $record->phone ?: '-' }}</span>
            </div>
            <div class="upm-card">
                <span class="upm-label">{{ __('user.label.address') }}</span>
                <span class="upm-value">{{ $record->address ?: '-' }}</span>
            </div>
        </div>

        @if(count($record->social_links ?? []) > 0)
            <span class="upm-section-title">{{ __('user.label.social_media') }}</span>
            <div class="upm-social-list">
                @foreach($record->social_links as $link)
                    <a href="{{ str_starts_with($link['url'], 'http') ? $link['url'] : 'https://' . $link['url'] }}"
                        target="_blank" class="upm-social-link">
                        <span style="text-transform: capitalize;">{{ $link['platform'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="upm-footer">
            <span>{{ __('user.label.created_at') }}: {{ $record->created_at->format('d/m/Y') }}</span>
            <span>{{ __('user.label.updated_at') }}: {{ $record->updated_at->format('d/m/Y') }}</span>
        </div>
    </div>
</div>