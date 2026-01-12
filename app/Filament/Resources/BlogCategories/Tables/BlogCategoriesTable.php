<?php

namespace App\Filament\Resources\BlogCategories\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Resources\BlogCategories\BlogCategoryResource;
use App\Services\BlogCategoryDeletionService;
class BlogCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->url(fn($record) => BlogCategoryResource::getUrl('index', ['parent_id' => $record->id])),
                TextColumn::make('language.name')
                    ->sortable(),
                TextColumn::make('parent.title')
                    ->label('Parent Category')
                    ->sortable(),
                TextColumn::make('sort')
                    ->sortable(),
                IconColumn::make('is_published')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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
