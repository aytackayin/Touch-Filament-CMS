<?php

namespace App\Filament\Resources\Languages\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Language;
use App\Filament\Resources\Languages\LanguageResource;

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
                    ->boolean()
                    ->action(function (Language $record) {
                        if ($record->is_default) {
                            // Varsayılan dil ise kapatmaya çalışılıyor
                            // Başka varsayılan dil var mı kontrol et
                            $hasAnotherDefault = Language::query()
                                ->where('id', '!=', $record->id)
                                ->where('is_default', true)
                                ->exists();

                            // Başka varsayılan yoksa bu dili varsayılan olmaktan çıkaramayız
                            if (!$hasAnotherDefault) {
                                return;
                            }
                        }

                        // Toggle yap
                        $newValue = !$record->is_default;

                        if ($newValue) {
                            // Bu dili varsayılan yapıyoruz, diğerlerini kapat
                            Language::query()
                                ->where('id', '!=', $record->id)
                                ->update(['is_default' => false]);
                        }

                        $record->is_default = $newValue;
                        $record->save();
                    }),
                IconColumn::make('is_active')
                    ->boolean()
                    ->action(function (Language $record) {
                        // Varsayılan dil pasif yapılamaz
                        if ($record->is_default && $record->is_active) {
                            return;
                        }

                        // Toggle yap
                        $record->is_active = !$record->is_active;
                        $record->save();
                    }),
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
                    ->url(fn(Language $record) => LanguageResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil-square')
                    ->label('')
                    ->tooltip('Edit'),
                DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip(__('button.delete'))
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->delete())
                    ->visible(fn($record) => !$record->is_default)
                    ->disabled(fn($record) => $record->is_default),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Delete selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->delete()),
                ]),
            ]);

    }
}
