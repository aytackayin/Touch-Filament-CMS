<div class="p-6 w-full text-start">
    @php
        $resolution = '';
        if ($record->type === 'image') {
            try {
                $path = \Illuminate\Support\Facades\Storage::disk('attachments')->path($record->path);
                if (file_exists($path)) {
                    [$w, $h] = getimagesize($path);
                    $resolution = "| {$w}x{$h} px";
                }
            } catch (\Exception $e) {
            }
        }
    @endphp

    {{-- Media Section --}}
    @if($record->type === 'image')
        <div class="w-full flex justify-start" style="margin-bottom: 16px;">
            <img src="{{ $url }}" alt="{{ $record->name }}"
                class="block max-w-full h-auto rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
        </div>
    @elseif($record->type === 'video')
        <div class="w-full" style="margin-bottom: 16px;">
            <video controls class="block max-w-full h-auto rounded-xl shadow-md border border-gray-200 dark:border-gray-700"
                style="max-height: 70vh;">
                <source src="{{ $url }}" type="{{ $record->mime_type }}">
                {{ __('file_manager.label.video_support_error') }}
            </video>
        </div>
    @endif

    <div class="w-full text-gray-700 dark:text-gray-300 leading-relaxed space-y-3">

        {{-- 1. Alt Text --}}
        @if(!empty($record->alt))
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 italic" style="margin-top: 0;">
                {{ $record->alt }}
            </p>
        @endif

        {{-- 2. Tags (Under Alt) --}}
        @if($record->tags && is_array($record->tags) && count($record->tags) > 0)
            <div class="flex flex-wrap items-center gap-2 pt-1"
                style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px;">
                <strong class="text-sm font-bold text-gray-900 dark:text-gray-100 italic"
                    style="margin-right: 4px;">{{ __('file_manager.label.tags') }}
                    :</strong>
                @foreach($record->tags as $tag)
                    <span
                        style="display: inline-flex; align-items: center; border-radius: 6px; background-color: rgba(156, 163, 175, 0.15); padding: 2px 8px; font-size: 0.75rem; font-weight: 500; color: currentColor; opacity: 0.8 !important; border: 1px solid rgba(156, 163, 175, 0.2);">
                        {{ $tag }}
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Data Rows --}}
        <div class="space-y-1.5 pt-1">
            <p class="text-sm">
                <strong
                    class="font-bold text-gray-900 dark:text-gray-100 italic">{{ __('file_manager.label.file_name') }}
                    :</strong>
                <span style="opacity: 0.7 !important; display: inline-block;">{{ $record->name }}</span>
            </p>

            <p class="text-sm">
                <strong
                    class="font-bold text-gray-900 dark:text-gray-100 italic">{{ __('file_manager.label.type_size') }}
                    :</strong>
                <span
                    style="opacity: 0.7 !important; display: inline-block;">{{ __('file_manager.label.types.' . ($record->type ?? 'other')) }}
                    |
                    {{ $record->human_size }} {{ $resolution }}</span>
            </p>

            <p class="text-sm">
                <strong
                    class="font-bold text-gray-900 dark:text-gray-100 italic">{{ __('file_manager.label.file_path') }}
                    :</strong>
                <span style="opacity: 0.7 !important; display: inline-block;">{{ $record->full_path }}</span>
            </p>
        </div>
    </div>
</div>