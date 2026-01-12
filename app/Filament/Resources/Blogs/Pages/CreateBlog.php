<?php

namespace App\Filament\Resources\Blogs\Pages;

use App\Filament\Resources\Blogs\BlogResource;
use Filament\Resources\Pages\CreateRecord;


class CreateBlog extends CreateRecord
{
    protected static string $resource = BlogResource::class;

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
