<?php

namespace App\Filament\Admin\Resources\BlogCategories\Pages;

use App\Filament\Admin\Resources\BlogCategories\BlogCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\BlogCategory;

class ListBlogCategories extends ListRecords
{
    protected static string $resource = BlogCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    public ?int $parent_id = null; // Üst kategori ID state
    // 🔹 URL parametresinden parent_id'yi almak için:
    public function mount(): void
    {
        parent::mount();

        $this->parent_id = request()->query('parent_id');
    }
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl() => static::getResource()::getBreadcrumb(),
        ];

        if ($this->parent_id) {
            $category = BlogCategory::find($this->parent_id);
            $trail = [];
            while ($category) {
                array_unshift($trail, [
                    'url' => static::getUrl(['parent_id' => $category->id]),
                    'label' => $category->title,
                ]);
                $category = $category->parent;
            }

            foreach ($trail as $crumb) {
                $breadcrumbs[$crumb['url']] = $crumb['label'];
            }
        }

        $breadcrumbs[] = $this->getBreadcrumb();

        return $breadcrumbs;
    }
}
