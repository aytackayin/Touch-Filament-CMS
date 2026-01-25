<?php

namespace App\Filament\Resources\Blogs\Pages;

use App\Filament\Resources\Blogs\BlogResource;
use App\Filament\Exports\BlogExporter;
use App\Filament\Imports\BlogImporter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\CheckboxList;
use App\Traits\HasTableSettings;

class ListBlogs extends ListRecords
{
    use HasTableSettings;
    protected static string $resource = BlogResource::class;

    #[\Livewire\Attributes\Url]
    public string $view_type = 'list';

    public array $visibleColumns = [];

    public function mount(): void
    {
        parent::mount();
        $this->mountHasTableSettings();
    }

    protected function getTableSettingsKey(): string
    {
        return 'blog_list';
    }

    protected function getDefaultVisibleColumns(): array
    {
        return ['categories', 'user', 'is_published', 'created_at'];
    }

    protected function getTableColumnOptions(): array
    {
        return [
            'categories' => __('blog.label.categories'),
            'language' => __('blog.label.language'),
            'user' => __('blog.label.author'),
            'editor' => __('blog.label.last_edited_by'),
            'tags' => __('blog.label.tags'),
            'is_published' => __('blog.label.is_published'),
            'created_at' => __('blog.label.created_at'),
        ];
    }

    protected function applySettings(array $settings): void
    {
        $this->visibleColumns = $settings['visible_columns'] ?? [];
        if (isset($settings['view_type']) && in_array($settings['view_type'], ['grid', 'list'])) {
            $this->view_type = $settings['view_type'];
        }
    }

    protected function getTableSettingsFormSchema(): array
    {
        return [
            Radio::make('view_type')
                ->label(__('blog.label.default_view'))
                ->options([
                    'list' => __('file_manager.label.list_view'),
                    'grid' => __('file_manager.label.grid_view'),
                ])
                ->default($this->view_type)
                ->inline()
                ->required(),
            CheckboxList::make('visible_columns')
                ->label(__('table_settings.columns'))
                ->options($this->getTableColumnOptions())
                ->default($this->visibleColumns)
                ->required()
                ->columns(2),
        ];
    }

    public function getTableExtraAttributes(): array
    {
        return [
            'class' => 'blogs-container ' . ($this->view_type === 'grid' ? 'is-grid-view' : 'is-list-view'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ExportAction::make()
                    ->label('')
                    ->icon(Heroicon::OutlinedArrowUpOnSquareStack)
                    ->tooltip(__('filament-actions::export.modal.actions.export.label'))
                    ->color('gray')
                    ->size('xs')
                    ->exporter(BlogExporter::class)
                    ->visible(fn() => auth()->user()->can('export', BlogResource::getModel())),
                ImportAction::make()
                    ->label('')
                    ->icon(Heroicon::OutlinedArrowDownOnSquareStack)
                    ->tooltip(__('filament-actions::import.modal.actions.import.label'))
                    ->color('gray')
                    ->size('xs')
                    ->importer(BlogImporter::class)
                    ->visible(fn() => auth()->user()->can('import', BlogResource::getModel())),
                CreateAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::create.single.modal.actions.create.label'))
                    ->color('success')
                    ->size('xs')
                    ->icon('heroicon-m-document-plus'),
            ])->buttonGroup(),
            $this->getTableSettingsAction(),

            Action::make('toggleView')
                ->label($this->view_type === 'grid' ? __('file_manager.label.list_view') : __('file_manager.label.grid_view'))
                ->tooltip($this->view_type === 'grid' ? __('file_manager.label.list_view') : __('file_manager.label.grid_view'))
                ->hiddenLabel()
                ->icon($this->view_type === 'grid' ? 'heroicon-o-list-bullet' : 'heroicon-o-squares-2x2')
                ->color('gray')
                ->size('xs')
                ->action(function () {
                    $newView = $this->view_type === 'grid' ? 'list' : 'grid';
                    $this->saveTableSettings(array_merge(
                        ['visible_columns' => $this->visibleColumns],
                        ['view_type' => $newView]
                    ));
                    return redirect(static::getResource()::getUrl('index', [
                        'view_type' => $newView,
                    ]));
                }),
        ];
    }
}
