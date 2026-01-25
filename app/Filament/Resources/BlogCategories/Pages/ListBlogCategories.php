<?php

namespace App\Filament\Resources\BlogCategories\Pages;

use App\Filament\Resources\BlogCategories\BlogCategoryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Blogs\Widgets\RelatedItemsWidget;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use App\Filament\Exports\BlogCategoryExporter;
use App\Filament\Imports\BlogCategoryImporter;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\CheckboxList;

class ListBlogCategories extends ListRecords
{
    protected static string $resource = BlogCategoryResource::class;

    public ?int $parent_id = null;
    public array $visibleColumns = [];

    public function mount(): void
    {
        parent::mount();

        $this->parent_id = request()->query('parent_id') ? (int) request()->query('parent_id') : null;

        // Load visible columns from user preferences
        $userPrefs = \App\Models\UserPreference::getTableSettings('blog_category_list');
        $this->visibleColumns = $userPrefs['visible_columns'] ?? ['tags', 'user', 'is_published'];
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
                    ->tooltip(__('blog.label.parent_category'))
                    ->color('gray')
                    ->size('xs')
                    ->translateLabel()
                    ->icon('heroicon-m-arrow-uturn-up')
                    ->url(static::getResource()::getUrl('index', $upParams))
            ];
        }

        $actions[] = ExportAction::make()
            ->label('')
            ->icon(Heroicon::OutlinedArrowUpOnSquareStack)
            ->tooltip(__('filament-actions::export.modal.actions.export.label'))
            ->color('gray')
            ->size('xs')
            ->exporter(BlogCategoryExporter::class)
            ->visible(fn() => auth()->user()->can('export', BlogCategoryResource::getModel()));

        $actions[] = ImportAction::make()
            ->label('')
            ->icon(Heroicon::OutlinedArrowDownOnSquareStack)
            ->tooltip(__('filament-actions::import.modal.actions.import.label'))
            ->color('gray')
            ->size('xs')
            ->importer(BlogCategoryImporter::class)
            ->visible(fn() => auth()->user()->can('import', BlogCategoryResource::getModel()));

        $actions[] = Action::make('tableSettings')
            ->label(__('blog.label.table_settings'))
            ->tooltip(__('blog.label.table_settings'))
            ->hiddenLabel()
            ->icon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->size('xs')
            ->form([
                Section::make(__('blog.label.table_settings'))
                    ->schema([
                        CheckboxList::make('visible_columns')
                            ->label(__('blog.label.visible_columns'))
                            ->options([
                                'tags' => __('blog.label.tags'),
                                'user' => __('blog.label.author'),
                                'editor' => __('blog.label.last_edited_by'),
                                'parent' => __('blog.label.parent_category'),
                                'is_published' => __('blog.label.is_published'),
                                'created_at' => __('blog.label.created_at'),
                            ])
                            ->default(fn() => \App\Models\UserPreference::getTableSettings('blog_category_list')['visible_columns'] ?? ['tags', 'user', 'is_published'])
                            ->columns(2),
                    ]),
            ])
            ->action(function (array $data) {
                \App\Models\UserPreference::setTableSettings('blog_category_list', $data);

                \Filament\Notifications\Notification::make()
                    ->title(__('blog.label.settings_saved'))
                    ->success()
                    ->send();

                return redirect(static::getResource()::getUrl('index', $this->parent_id ? ['parent_id' => $this->parent_id] : []));
            });

        $actions[] =
            CreateAction::make()
                ->label('')
                ->tooltip(__('filament-actions::create.single.modal.actions.create.label'))
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
