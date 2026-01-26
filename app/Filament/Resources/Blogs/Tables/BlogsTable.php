<?php

namespace App\Filament\Resources\Blogs\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use App\Models\Blog;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Exports\BlogExporter;
use Filament\Actions\ExportBulkAction;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\Blogs\BlogResource;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;

class BlogsTable
{
    public static function configure(Table $table): Table
    {
        $livewire = $table->getLivewire();
        $isGrid = ($livewire && property_exists($livewire, 'view_type'))
            ? $livewire->view_type === 'grid'
            : false;

        return $table
            ->when(
                $isGrid,
                fn(Table $table) => $table
                    ->contentGrid([
                        'md' => 2,
                        'xl' => 3,
                        '2xl' => 4,
                    ])
                    ->extraAttributes([
                        'class' => 'blogs-grid',
                    ])
            )
            ->striped()
            ->paginatedWhileReordering()
            ->recordUrl(null)
            ->columns($isGrid ? [
                Stack::make([
                    ViewColumn::make('details')
                        ->view('filament.tables.columns.blog-grid')->searchable(['title', 'content', 'tags']),
                ])->space(0),
            ] : [
                ImageColumn::make('cover_thumbnail')
                    ->label('')
                    ->disk('attachments')
                    ->state(fn(Blog $record) => $record->getThumbnailPath())
                    ->defaultImageUrl(fn(Blog $record) => url(config('blog.icon_paths.base') . config('blog.icon_paths.file')))
                    ->square()
                    ->extraImgAttributes(['style' => 'border-radius: 8px !important; object-fit: cover;'])
                    ->size(40),
                TextColumn::make('title')
                    ->label(__('blog.label.title'))
                    ->searchable(['title', 'content'])
                    ->description(fn(Blog $record): HtmlString => $record->content ? new HtmlString('<span style="font-size: 12px; line-height: 1;" class="text-gray-500 dark:text-gray-400">' . Str::limit(strip_tags($record->content), 100) . '</span>') : new HtmlString(''))
                    ->wrap()
                    ->sortable(),
                TextColumn::make('categories.title')
                    ->label(__('blog.label.categories'))
                    ->badge()
                    ->icon('heroicon-s-folder')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->toggleable()
                    ->hidden(fn($livewire) => !in_array('categories', $livewire->visibleColumns ?? [])),
                TextColumn::make('language.name')
                    ->label(__('blog.label.language'))
                    ->sortable()
                    ->toggleable()
                    ->hidden(fn($livewire) => !in_array('language', $livewire->visibleColumns ?? [])),
                TextColumn::make('user.name')
                    ->label(__('blog.label.author'))
                    ->sortable()
                    ->toggleable()
                    ->hidden(fn($livewire) => !in_array('user', $livewire->visibleColumns ?? [])),
                TextColumn::make('editor.name')
                    ->label(__('blog.label.last_edited_by'))
                    ->sortable()
                    ->toggleable()
                    ->hidden(fn($livewire) => !in_array('editor', $livewire->visibleColumns ?? [])),
                TextColumn::make('tags')
                    ->label(__('blog.label.tags'))
                    ->badge()
                    ->icon('heroicon-s-tag')
                    ->separator(',')
                    ->searchable()
                    ->toggleable()
                    ->hidden(fn($livewire) => !in_array('tags', $livewire->visibleColumns ?? [])),
                IconColumn::make('is_published')
                    ->label(__('blog.label.is_published'))
                    ->size(IconSize::Medium)
                    ->alignCenter(true)
                    ->sortable()
                    ->boolean()
                    ->toggleable()
                    ->hidden(fn($livewire) => !in_array('is_published', $livewire->visibleColumns ?? []))
                    ->action(function ($record) {
                        if (auth()->user()->can('update', $record)) {
                            $record->is_published = !$record->is_published;
                            $record->save();
                        }
                    }),
                TextColumn::make('created_at')
                    ->label(__('blog.label.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->hidden(fn($livewire) => !in_array('created_at', $livewire->visibleColumns ?? [])),
            ])
            ->reorderable('sort')
            ->defaultSort(column: 'created_at', direction: 'desc')
            ->filters([
                SelectFilter::make('user_id')
                    ->label(__('blog.label.author'))
                    ->relationship('user', 'name')
                    ->searchable(),
                SelectFilter::make('edit_user_id')
                    ->label(__('blog.label.last_edited_by'))
                    ->relationship('editor', 'name')
                    ->searchable(),
                SelectFilter::make('language_id')
                    ->label(__('blog.label.language'))
                    ->relationship('language', 'name'),
                Filter::make('categories')
                    ->label(__('blog.label.categories'))
                    ->form([
                        SelectTree::make('categories')
                            ->label(__('blog.label.categories'))
                            ->relationship('categories', 'title', 'parent_id')
                            ->enableBranchNode()
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['categories'],
                            fn(Builder $query, $categories): Builder => $query->whereHas('categories', fn(Builder $query) => $query->whereIn('blog_categories.id', (array) $categories)),
                        );
                    }),
                SelectFilter::make('is_published')
                    ->label(__('blog.label.is_published'))
                    ->options([
                        '1' => __('blog.label.published'),
                        '0' => __('blog.label.draft'),
                    ]),
            ])
            ->actions([
                Action::make('view')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip(__('filament-actions::view.single.label'))
                    ->color('gray')
                    ->modalHeading(fn($record) => $record->title)
                    ->modalContent(fn($record) => view('filament.resources.blogs.modals.view-content', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('filament-actions::view.single.modal.actions.close.label'))
                    ->modalWidth('5xl'),
                EditAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::edit.single.label'))
                    ->url(fn(Blog $record): string => BlogResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::delete.single.label'))
                    ->modalHeading(fn(Blog $record) => __('blog.delete_confirmation_title.blog', ['name' => $record->title]))
                    ->modalDescription(__('blog.delete_confirmation_description')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('filament-actions::delete.multiple.label'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn() => auth()->user()->can('deleteAny', Blog::class))
                        ->action(fn($records) => $records->filter(fn($blog) => auth()->user()->can('delete', $blog))->each->delete()),
                    ExportBulkAction::make()
                        ->label(__('filament-actions::export.modal.actions.export.label'))
                        ->icon(Heroicon::OutlinedArrowUpOnSquareStack)
                        ->exporter(BlogExporter::class),
                ]),
            ]);
    }
}
