<?php

namespace App\Filament\Resources\Blogs\Pages;

use App\Filament\Resources\Blogs\BlogResource;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Url;


class CreateBlog extends CreateRecord
{
    protected static string $resource = BlogResource::class;

    public ?string $previousUrl = null;

    #[Url]
    public ?string $category_id = null;

    public ?string $video_thumbnails_store = null;

    protected function fillForm(): void
    {
        parent::fillForm();

        if ($this->category_id) {
            $this->data['categories'] = [(int) $this->category_id];
        }
    }

    public function mount(): void
    {
        parent::mount();

        $this->previousUrl = url()->previous();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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
