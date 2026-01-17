<div class="p-6" style="text-align: center; width: 100%;">
    @if($record->type === 'image')
        <div style="margin-bottom: 20px; display: block; width: 100%;">
            <img src="{{ $url }}" alt="{{ $record->name }}"
                style="display: block; margin: 0 auto; max-width: 100%; height: auto; border-radius: 12px; shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.1);">
        </div>
    @elseif($record->type === 'video')
        <div style="margin-bottom: 20px; display: block; width: 100%;">
            <video controls
                style="display: block; margin: 0 auto; max-width: 100%; height: auto; border-radius: 12px; shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.1); max-height: 70vh;">
                <source src="{{ $url }}" type="{{ $record->mime_type }}">
                Your browser does not support the video tag.
            </video>
        </div>
    @endif

    <div style="display: block; width: 100%; margin-top: 15px; text-align: center; color: #6b7280;">
        <p style="font-weight: 900; font-size: 1rem; margin-bottom: 5px;">{{ $record->name }}</p>
        <div style="display: flex; justify-content: center; gap: 15px; font-size: 0.875rem;">
            <span><strong>Type:</strong> {{ ucfirst($record->type) }}</span>
            <span><strong>Size:</strong> {{ $record->human_size }}</span>
        </div>
        @if($record->parent)
            <p style="font-size: 0.875rem; margin-top: 5px;"><strong>Location:</strong> {{ $record->parent->full_path }}</p>
        @endif
        @if(!empty($record->alt))
            <p style="font-size: 0.875rem; margin-top: 5px; color: #4b5563;">
                {{ $record->alt }}
            </p>
        @endif
    </div>
</div>