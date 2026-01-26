<?php

namespace App\Filament\Resources\BlogCategories\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Filament\Resources\BlogCategories\BlogCategoryResource;
use App\Services\BlogCategoryDeletionService;
use App\Models\BlogCategory;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use App\Filament\Exports\BlogCategoryExporter;
use Filament\Actions\ExportBulkAction;
class BlogCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->paginatedWhileReordering()
            ->recordUrl(null)
            ->columns([
                ImageColumn::make('cover_thumbnail')
                    ->label('')
                    ->disk('attachments')
                    ->state(fn(BlogCategory $record) => $record->getThumbnailPath())
                    ->defaultImageUrl(fn(BlogCategory $record) => url(config('blogcategory.icon_paths.base') . config('blogcategory.icon_paths.file')))
                    ->square()
                    ->extraImgAttributes(['style' => 'border-radius: 8px !important; object-fit: cover;'])
                    ->size(40),
                TextColumn::make('title')
                    ->label(__('blog.label.category'))
                    ->searchable(['title', 'description', 'tags'])
                    ->icon('heroicon-m-squares-2x2')
                    ->sortable()
                    ->color('primary')
                    ->description(fn(BlogCategory $record): HtmlString => $record->description ? new HtmlString('<span style="font-size: 12px; line-height: 1;" class="text-gray-500 dark:text-gray-400">' . Str::limit(strip_tags($record->description), 100) . '</span>') : new HtmlString(''))
                    ->wrap()
                    ->url(fn($record) => BlogCategoryResource::getUrl('index', ['parent_id' => $record->id])),
                TextColumn::make('language.name')
                    ->label(__('blog.label.language'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tags')
                    ->label(__('blog.label.tags'))
                    ->badge()
                    ->separator(',')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('tags', $livewire->visibleColumns ?? [])),
                TextColumn::make('user.name')
                    ->label(__('blog.label.author'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('user', $livewire->visibleColumns ?? [])),
                TextColumn::make('editor.name')
                    ->label(__('blog.label.last_edited_by'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('editor', $livewire->visibleColumns ?? [])),
                TextColumn::make('parent.title')
                    ->label(__('blog.label.parent_category'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('parent', $livewire->visibleColumns ?? [])),
                IconColumn::make('is_published')
                    ->label(__('blog.label.is_published'))
                    ->size(IconSize::Medium)
                    ->alignCenter(true)
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('is_published', $livewire->visibleColumns ?? []))
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
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('created_at', $livewire->visibleColumns ?? [])),
            ])
            ->reorderable('sort')
            ->defaultSort('sort', 'asc')
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
                SelectFilter::make('is_published')
                    ->label(__('blog.label.is_published'))
                    ->options([
                        '1' => __('blog.label.published'),
                        '0' => __('blog.label.draft'),
                    ]),
            ])
            ->actions([
                ViewAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::view.single.label')),
                EditAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::edit.single.label'))
                    ->url(fn(BlogCategory $record): string => BlogCategoryResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::delete.single.label'))
                    ->modalHeading(fn(BlogCategory $record) => __('blog.delete_confirmation_title.category', ['name' => $record->title]))
                    ->modalDescription(__('blog.delete_confirmation_description'))
                    ->action(function ($record) {
                        $deletionService = app(BlogCategoryDeletionService::class);
                        $deletionService->delete($record);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('filament-actions::delete.multiple.label'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn() => auth()->user()->can('deleteAny', BlogCategory::class))
                        ->action(function ($records) {
                            $deletionService = app(BlogCategoryDeletionService::class);
                            foreach ($records as $record) {
                                if (auth()->user()->can('delete', $record)) {
                                    $deletionService->delete($record);
                                }
                            }
                        }),
                    ExportBulkAction::make()
                        ->label(__('filament-actions::export.modal.actions.export.label'))
                        ->icon(Heroicon::OutlinedArrowUpOnSquareStack)
                        ->exporter(BlogCategoryExporter::class),
                ]),
            ]);
    }
}
