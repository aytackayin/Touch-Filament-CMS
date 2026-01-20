<?php

namespace App\Filament\Resources\BlogCategories\Pages;

use App\Filament\Resources\BlogCategories\BlogCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditBlogCategory extends EditRecord
{
    protected static string $resource = BlogCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public ?string $previousUrl = null;

    public function mount(int|string $record): void
    {
        // Reverse Synchronization: Check disk files and update record
        $this->syncAttachmentsFromDisk($record);

        parent::mount($record);

        $this->previousUrl = url()->previous();
    }

    protected function syncAttachmentsFromDisk($recordId): void
    {
        // We need the model instance, parent::mount hasn't run fully yet but we can resolve it
        // Or simpler: handle it after resolving record.
        // Since we are overriding mount, we can do it before calling parent::mount if we resolve recordmanually
        // or just let parent::mount run first, but then data is already filled.
        // Actually, better to do it BEFORE parent::mount so the form gets fresh data.

        $record = $this->resolveRecord($recordId);

        if (!$record)
            return;

        $disk = Storage::disk('attachments');
        $baseDir = "blog_categories/{$record->id}";

        if ($disk->exists($baseDir)) {
            // Get all files recursively
            $allFiles = $disk->allFiles($baseDir);

            // Filter: Exclude thumbs
            $validAttachments = array_filter($allFiles, function ($path) {
                return !str_contains($path, '/thumbs/');
            });

            // Normalize paths (ensure forward slashes) and re-index
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

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
