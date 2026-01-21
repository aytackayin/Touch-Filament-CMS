<?php

namespace App\Filament\Resources\BlogCategories\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
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
                TextColumn::make('title')
                    ->label(__('blog.label.category'))
                    ->searchable(['title', 'description'])
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
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->label(__('blog.label.author'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('editor.name')
                    ->label(__('blog.label.last_edited_by'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('parent.title')
                    ->label(__('blog.label.parent_category'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_published')
                    ->label(__('blog.label.is_published'))
                    ->size(IconSize::Medium)
                    ->alignCenter(true)
                    ->boolean()
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
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        '1' => 'Published',
                        '0' => 'Unpublished',
                    ]),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::view.single.label')),
                EditAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::edit.single.label')),
                DeleteAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::delete.single.label'))
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
