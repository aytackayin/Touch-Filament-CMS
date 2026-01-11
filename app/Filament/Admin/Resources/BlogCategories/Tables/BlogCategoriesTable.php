<?php

namespace App\Filament\Admin\Resources\BlogCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlogCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                $parentId = request()->query('parent_id');
                if ($parentId) {
                    $query->where('parent_id', $parentId);
                } else {
                    $query->whereNull('parent_id');
                }
            })
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->url(fn(\App\Models\BlogCategory $record) => \App\Filament\Admin\Resources\BlogCategories\BlogCategoryResource::getUrl('index', ['parent_id' => $record->id])),
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
            ->actions([
                EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->label('')
                    ->tooltip(__('button.edit')),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->label('')
                    ->tooltip(__('button.delete')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
