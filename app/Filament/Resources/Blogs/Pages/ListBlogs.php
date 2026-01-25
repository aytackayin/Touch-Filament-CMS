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

class ListBlogs extends ListRecords
{
    protected static string $resource = BlogResource::class;

    #[\Livewire\Attributes\Url]
    public string $view_type = 'list';

    public array $visibleColumns = [];

    public function mount(): void
    {
        parent::mount();

        // Priority: 1. Cookie (browser-specific), 2. User Preference (user-specific), 3. Default
        if (request()->query('view_type') === null) {
            $cookieView = request()->cookie('blog_view_type');
            if ($cookieView && in_array($cookieView, ['grid', 'list'])) {
                $this->view_type = $cookieView;
            } else {
                $userPrefs = \App\Models\UserPreference::getTableSettings('blog_list');
                if ($userPrefs && isset($userPrefs['view_type'])) {
                    $this->view_type = $userPrefs['view_type'];
                }
            }
        }

        // Load visible columns from user preferences
        $userPrefs = \App\Models\UserPreference::getTableSettings('blog_list');
        $this->visibleColumns = $userPrefs['visible_columns'] ?? ['categories', 'user', 'is_published'];
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
            Action::make('tableSettings')
                ->label(__('blog.label.table_settings'))
                ->tooltip(__('blog.label.table_settings'))
                ->hiddenLabel()
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->size('xs')
                ->form([
                    Section::make(__('blog.label.table_settings'))
                        ->schema([
                            Radio::make('view_type')
                                ->label(__('blog.label.default_view'))
                                ->options([
                                    'list' => __('file_manager.label.list_view'),
                                    'grid' => __('file_manager.label.grid_view'),
                                ])
                                ->default(fn() => \App\Models\UserPreference::getTableSettings('blog_list')['view_type'] ?? 'list')
                                ->inline()
                                ->required(),
                            CheckboxList::make('visible_columns')
                                ->label(__('blog.label.visible_columns'))
                                ->options([
                                    'categories' => __('blog.label.categories'),
                                    'language' => __('blog.label.language'),
                                    'user' => __('blog.label.author'),
                                    'editor' => __('blog.label.last_edited_by'),
                                    'tags' => __('blog.label.tags'),
                                    'is_published' => __('blog.label.is_published'),
                                    'created_at' => __('blog.label.created_at'),
                                ])
                                ->default(fn() => \App\Models\UserPreference::getTableSettings('blog_list')['visible_columns'] ?? ['categories', 'user', 'is_published'])
                                ->columns(2),
                        ]),
                ])
                ->action(function (array $data) {
                    \App\Models\UserPreference::setTableSettings('blog_list', $data);

                    \Filament\Notifications\Notification::make()
                        ->title(__('blog.label.settings_saved'))
                        ->success()
                        ->send();

                    // Redirect to refresh table with new settings
                    return redirect(static::getResource()::getUrl('index'));
                }),
            Action::make('toggleView')
                ->label($this->view_type === 'grid' ? __('file_manager.label.list_view') : __('file_manager.label.grid_view'))
                ->tooltip($this->view_type === 'grid' ? __('file_manager.label.list_view') : __('file_manager.label.grid_view'))
                ->hiddenLabel()
                ->icon($this->view_type === 'grid' ? 'heroicon-o-list-bullet' : 'heroicon-o-squares-2x2')
                ->color('gray')
                ->size('xs')
                ->action(function () {
                    $newView = $this->view_type === 'grid' ? 'list' : 'grid';

                    // Store preferred view in cookie for 1 year
                    cookie()->queue(cookie()->forever('blog_view_type', $newView));

                    return redirect(static::getResource()::getUrl('index', [
                        'view_type' => $newView,
                    ]));
                }),
        ];
    }
}
