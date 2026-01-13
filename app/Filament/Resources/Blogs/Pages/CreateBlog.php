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

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
