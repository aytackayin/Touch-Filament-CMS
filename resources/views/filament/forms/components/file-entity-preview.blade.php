<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-4">
    @if($record->type === 'image')
        <div class="flex justify-center">
            <img src="{{ $record->url }}" alt="{{ $record->name }}" class="max-h-96 rounded-lg shadow-sm">
        </div>
    @elseif($record->type === 'video')
        <div class="flex justify-center">
            <video controls class="max-h-96 rounded-lg shadow-sm">
                <source src="{{ $record->url }}" type="{{ $record->mime_type }}">
                Your browser does not support the video tag.
            </video>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-8 text-gray-500">
            <x-filament::icon icon="{{ $record->icon }}" class="h-16 w-16 mb-2" />
            <span class="text-lg font-medium">No preview available</span>
            <span class="text-sm">Download to view content</span>
        </div>
    @endif

    <div class="mt-4 flex justify-center space-x-4">
        <x-filament::button tag="a" href="{{ $record->url }}" target="_blank" icon="heroicon-o-arrow-down-tray"
            color="gray" size="sm">
            Download
        </x-filament::button>

        <x-filament::button tag="a" href="{{ $record->url }}" target="_blank" icon="heroicon-o-eye" color="gray"
            size="sm">
            Open in New Tab
        </x-filament::button>
    </div>
</div>