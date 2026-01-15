<div class="p-4">
    @if($record->type === 'image')
        <div class="flex justify-center">
            <img src="{{ $url }}" alt="{{ $record->name }}" class="max-w-full h-auto rounded-lg shadow-lg">
        </div>
    @elseif($record->type === 'video')
        <div class="flex justify-center">
            <video controls class="max-w-full h-auto rounded-lg shadow-lg" style="max-height: 70vh;">
                <source src="{{ $url }}" type="{{ $record->mime_type }}">
                Your browser does not support the video tag.
            </video>
        </div>
    @endif

    <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
        <p><strong>Name:</strong> {{ $record->name }}</p>
        <p><strong>Type:</strong> {{ ucfirst($record->type) }}</p>
        <p><strong>Size:</strong> {{ $record->human_size }}</p>
        @if($record->parent)
            <p><strong>Location:</strong> {{ $record->parent->full_path }}</p>
        @endif
    </div>
</div>