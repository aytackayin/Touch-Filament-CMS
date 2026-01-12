<?php

namespace App\Filament\Resources\Languages\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class LanguagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('charset')
                    ->searchable(),
                TextColumn::make('direction')
                    ->badge(),
                IconColumn::make('is_default')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('edit')
                    ->url(fn(\App\Models\Language $record) => \App\Filament\Resources\Languages\LanguageResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil-square')
                    ->label('')
                    ->tooltip('Edit'),
                Action::make('delete')
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-trash')
                    ->modalHeading(fn($record) => __('filament-actions::delete.single.modal.heading', ['label' => $record->name]))
                    ->modalDescription(__('filament-actions::delete.single.modal.description'))
                    ->modalSubmitActionLabel(__('filament-actions::delete.single.modal.actions.delete.label'))
                    ->action(fn(\App\Models\Language $record) => $record->delete())
                    ->icon('heroicon-o-trash')
                    ->label('')
                    ->color('danger')
                    ->tooltip('Delete')
                    ->visible(fn($record) => !$record->is_default)
                    ->disabled(fn($record) => $record->is_default),
            ])
            ->bulkActions([
                BulkAction::make('delete')
                    ->label('Delete selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        if ($records->contains(fn($record) => $record->is_default)) {
                            Notification::make()
                                ->title('Default language cannot be deleted')
                                ->body('Please change the default language before deleting it.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $records->each->delete();

                        Notification::make()
                            ->title('Languages deleted')
                            ->success()
                            ->send();
                    }),
            ]);

    }
}
