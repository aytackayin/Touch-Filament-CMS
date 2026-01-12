<?php

namespace App\Filament\Resources\BlogCategories\Pages;

use App\Filament\Resources\BlogCategories\BlogCategoryResource;
use Filament\Resources\Pages\CreateRecord;


class CreateBlogCategory extends CreateRecord
{
    protected static string $resource = BlogCategoryResource::class;

    public ?string $previousUrl = null;

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
