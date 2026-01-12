<?php

namespace App\Filament\Resources\BlogCategories\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                    ->url(fn(\App\Models\BlogCategory $record) => \App\Filament\Resources\BlogCategories\BlogCategoryResource::getUrl('index', ['parent_id' => $record->id])),
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
                    ->url(fn(\App\Models\BlogCategory $record) => \App\Filament\Resources\BlogCategories\BlogCategoryResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil-square')
                    ->label('')
                    ->tooltip(__('button.edit')),
                Action::make('delete')
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-trash')
                    ->modalHeading(fn($record) => __('filament-actions::delete.single.modal.heading', ['label' => $record->title]))
                    ->modalDescription(__('filament-actions::delete.single.modal.description'))
                    ->modalSubmitActionLabel(__('filament-actions::delete.single.modal.actions.delete.label'))
                    ->action(fn(\App\Models\BlogCategory $record) => $record->delete())
                    ->icon('heroicon-o-trash')
                    ->label('')
                    ->color('danger')
                    ->tooltip(__('button.delete')),
            ])
            ->bulkActions([
                BulkAction::make('delete')
                    ->label('Delete selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn($records) => $records->each->delete()),
            ]);
    }
}
