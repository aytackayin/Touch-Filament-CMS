<?php

namespace App\Filament\Admin\Resources\BlogCategories\Pages;

use App\Filament\Admin\Resources\BlogCategories\BlogCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\BlogCategory;

class ListBlogCategories extends ListRecords
{
    protected static string $resource = BlogCategoryResource::class;

    public ?int $parent_id = null;
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

    protected function getHeaderActions(): array
    {
        $createParams = $this->parent_id ? ['parent_id' => $this->parent_id] : [];

        $actions = [
            Actions\CreateAction::make()
                ->label('')
                ->tooltip(__('button.new'))
                ->color('success')
                ->size('xs')
                ->icon('heroicon-m-squares-plus')
                ->url(static::getResource()::getUrl('create', $createParams)),
        ];

        if ($this->parent_id) {
            $parent = BlogCategory::find($this->parent_id);
            $upParams = ($parent && $parent->parent_id) ? ['parent_id' => $parent->parent_id] : [];

            $actions[] = Actions\Action::make('up')
                ->label('')
                ->tooltip(__('button.parent_category'))
                ->color('gray')
                ->size('xs')
                ->translateLabel()
                ->icon('heroicon-m-arrow-uturn-up')
                ->url(static::getResource()::getUrl('index', $upParams));
        }

        return $actions;
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
