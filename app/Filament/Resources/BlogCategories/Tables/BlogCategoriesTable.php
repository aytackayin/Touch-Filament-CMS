<?php

namespace App\Filament\Resources\BlogCategories\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Resources\BlogCategories\BlogCategoryResource;
use App\Services\BlogCategoryDeletionService;
use App\Models\BlogCategory;
use Illuminate\Support\Str;
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
                    ->description(fn(BlogCategory $record): string => $record->description ? Str::limit(strip_tags($record->description), 100) : '')
                    ->url(fn($record) => BlogCategoryResource::getUrl('index', ['parent_id' => $record->id])),
                TextColumn::make('language.name')
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
                        $record->is_published = !$record->is_published;
                        $record->save();
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->reorderable('sort')
            ->defaultSort('sort', 'asc')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('edit')
                    ->url(fn($record) => BlogCategoryResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil-square')
                    ->label('')
                    ->tooltip(__('button.edit')),
                DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip(__('button.delete'))
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->delete()),
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
