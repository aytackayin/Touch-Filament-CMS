<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->label(__('label.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('label.email'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('label.create_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('label.update_at'))
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
                    ->tooltip(__('button.edit'))
                    ->visible(fn($record) => auth()->user()->can('update', $record)),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->label('')
                    ->tooltip(__('button.delete'))
                    ->visible(fn($record) => auth()->user()->can('delete', $record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
