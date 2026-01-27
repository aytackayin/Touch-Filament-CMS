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
use App\Traits\HasTableSettings;

class ListBlogs extends ListRecords
{
    use HasTableSettings;
    protected static string $resource = BlogResource::class;

    #[\Livewire\Attributes\Url]
    public string $view_type = 'list';

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
                ->extraAttributes(fn() => [
                    'style' => sprintf(
                        'background-color:%s;color:white;',
                        $this->view_type === 'grid' ? '#d08700' : '#0ea5e9'
                    ),
                ])
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
