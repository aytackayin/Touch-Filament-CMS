<?php

namespace App\Filament\Resources\Blogs\Pages;

use App\Filament\Resources\Blogs\BlogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditBlog extends EditRecord
{
    protected static string $resource = BlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public ?string $previousUrl = null;

    public ?string $video_thumbnails_store = null;

    public function mount(int|string $record): void
    {
        $this->syncAttachmentsFromDisk($record);
        parent::mount($record);

        $this->previousUrl = url()->previous();
    }

    protected function syncAttachmentsFromDisk($recordId): void
    {
        $record = $this->resolveRecord($recordId);

        if (!$record)
            return;

        $disk = Storage::disk('attachments');
        $baseDir = "blogs/{$record->id}";

        if ($disk->exists($baseDir)) {
            // Get all files recursively
            $allFiles = $disk->allFiles($baseDir);

            // Filter: Exclude thumbs
            $validAttachments = array_filter($allFiles, function ($path) {
                return !str_contains($path, '/thumbs/');
            });

            // Normalize paths
            $validAttachments = array_values(array_map(function ($path) {
                return str_replace('\\', '/', $path);
            }, $validAttachments));

            // Compare
            $currentAttachments = $record->attachments ?? [];

            // Ensure it's an array (could be string from old data)
            if (!is_array($currentAttachments)) {
                $currentAttachments = [];
            }

            // Sort for comparison
            sort($validAttachments);
            sort($currentAttachments);

            if ($validAttachments !== $currentAttachments) {
                $record->attachments = $validAttachments;
                $record->saveQuietly();
            }
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Process video thumbnails if present
        if (!empty($this->video_thumbnails_store)) {
            $data['_video_thumbnails'] = $this->video_thumbnails_store;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
