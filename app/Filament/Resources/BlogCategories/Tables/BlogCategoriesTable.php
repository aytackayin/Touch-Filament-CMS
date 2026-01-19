<?php

namespace App\Filament\Resources\BlogCategories\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Filament\Resources\BlogCategories\BlogCategoryResource;
use App\Services\BlogCategoryDeletionService;
use App\Models\BlogCategory;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
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
                    ->searchable(['title', 'description'])
                    ->icon('heroicon-m-squares-2x2')
                    ->sortable()
                    ->color('primary')
                    ->description(fn(BlogCategory $record): HtmlString => $record->description ? new HtmlString('<span style="font-size: 12px; line-height: 1;" class="text-gray-500 dark:text-gray-400">' . Str::limit(strip_tags($record->description), 100) . '</span>') : new HtmlString(''))
                    ->wrap()
                    ->url(fn($record) => BlogCategoryResource::getUrl('index', ['parent_id' => $record->id])),
                TextColumn::make('language.name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tags')
                    ->badge()
                    ->separator(',')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('parent.title')
                    ->label('Parent Category')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_published')
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
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->reorderable(auth()->user()->can('reorder', \App\Models\BlogCategory::class) ? 'sort' : null)
            ->defaultSort('sort', 'asc')
            ->filters([
                SelectFilter::make('language_id')
                    ->label(__('label.language'))
                    ->relationship('language', 'name'),
                SelectFilter::make('is_published')
                    ->label('Publication Status')
                    ->options([
                        '1' => 'Published',
                        '0' => 'Unpublished',
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->label('')
                    ->tooltip(__('button.edit'))
                    ->visible(fn($record) => auth()->user()->can('update', $record)),
                DeleteAction::make()
                    ->label('')
                    ->tooltip(__('button.delete'))
                    ->visible(fn($record) => auth()->user()->can('delete', $record))
                    ->action(function ($record) {
                        $deletionService = app(BlogCategoryDeletionService::class);
                        $deletionService->delete($record);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Delete selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $deletionService = app(BlogCategoryDeletionService::class);
                            foreach ($records as $record) {
                                $deletionService->delete($record);
                            }
                        }),
                ]),
            ]);
    }
}
