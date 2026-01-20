<?php

namespace App\Filament\Resources\BlogCategories\Pages;

use App\Filament\Resources\BlogCategories\BlogCategoryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Blogs\Widgets\RelatedItemsWidget;

class ListBlogCategories extends ListRecords
{
    protected static string $resource = BlogCategoryResource::class;

    public ?int $parent_id = null;
    public function mount(): void
    {
        parent::mount();

        $this->parent_id = request()->query('parent_id') ? (int) request()->query('parent_id') : null;
    }

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();

        if ($this->parent_id) {
            $query->where('parent_id', $this->parent_id);
        } else {
            $query->whereNull('parent_id');
        }

        return $query;
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

        if ($this->parent_id) {
            $parent = BlogCategory::find($this->parent_id);
            $upParams = ($parent && $parent->parent_id) ? ['parent_id' => $parent->parent_id] : [];

            $actions = [
                Action::make('up')
                    ->label('')
                    ->tooltip(__('button.parent_category'))
                    ->color('gray')
                    ->size('xs')
                    ->translateLabel()
                    ->icon('heroicon-m-arrow-uturn-up')
                    ->url(static::getResource()::getUrl('index', $upParams))
            ];
        }

        $actions[] = \Filament\Actions\ExportAction::make()
            ->label('')
            ->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowUpOnSquareStack)
            ->tooltip(__('button.export'))
            ->color('gray')
            ->size('xs')
            ->exporter(\App\Filament\Exports\BlogCategoryExporter::class)
            ->visible(fn() => auth()->user()->can('export', BlogCategoryResource::getModel()));

        $actions[] = \Filament\Actions\ImportAction::make()
            ->label('')
            ->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowDownOnSquareStack)
            ->tooltip(__('button.import'))
            ->color('gray')
            ->size('xs')
            ->importer(\App\Filament\Imports\BlogCategoryImporter::class)
            ->visible(fn() => auth()->user()->can('import', BlogCategoryResource::getModel()));

        $actions[] =
            CreateAction::make()
                ->label('')
                ->tooltip(__('button.new'))
                ->color('success')
                ->size('xs')
                ->icon('heroicon-m-squares-plus')
                ->url(static::getResource()::getUrl('create', $createParams));

        return $actions;
    }

    public function getFooterWidgets(): array
    {
        if (!$this->parent_id) {
            return [];
        }

        return [
            RelatedItemsWidget::make([
                'parent_id' => $this->parent_id,
            ]),
        ];
    }
}
