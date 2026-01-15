<?php

namespace App\Filament\Resources\Blogs\Pages;

use App\Filament\Resources\Blogs\BlogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlog extends EditRecord
{
    protected static string $resource = BlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public ?string $previousUrl = null;

    public ?string $video_thumbnails_store = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->previousUrl = url()->previous();
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
