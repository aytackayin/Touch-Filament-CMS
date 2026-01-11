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
        $createParams = $this->parent_id ? ['parent_id' => $this->parent_id] : [];

        $actions = [
            Actions\CreateAction::make()
                ->url(static::getResource()::getUrl('create', $createParams)),
        ];

        if ($this->parent_id) {
            $parent = BlogCategory::find($this->parent_id);
            // If current view is sub-category (parent_id=X), "Up" means go to parent of X.
            // If X is root, "Up" means go to Root (index).
            $upParams = ($parent && $parent->parent_id) ? ['parent_id' => $parent->parent_id] : [];

            $actions[] = Actions\Action::make('up')
                ->label('Üst Kategoriye Dön')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('gray')
                ->url(static::getResource()::getUrl('index', $upParams));
        }

        return $actions;
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

    public function getFooterWidgets(): array
    {
        if (!$this->parent_id) {
            return [];
        }

        return [
            \App\Filament\Admin\Resources\Blogs\Widgets\RelatedItemsWidget::make([
                'parent_id' => $this->parent_id,
            ]),
        ];
    }
}
