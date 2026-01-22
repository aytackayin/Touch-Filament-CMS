<?php

namespace App\Filament\Resources\BlogCategories\Pages;

use App\Filament\Resources\BlogCategories\BlogCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Url;

class CreateBlogCategory extends CreateRecord
{
    protected static string $resource = BlogCategoryResource::class;

    public ?string $previousUrl = null;

    #[Url]
    public ?string $parent_id = null;

    protected function fillForm(): void
    {
        parent::fillForm();

        if ($this->parent_id) {
            $this->data['parent_id'] = $this->parent_id;
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
