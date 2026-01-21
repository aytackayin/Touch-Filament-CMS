<?php

namespace App\Filament\Resources\Languages\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\IconSize;
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
                    ->label(__('language.label.name'))
                    ->searchable(),
                TextColumn::make('code')
                    ->label(__('language.label.code'))
                    ->searchable(),
                TextColumn::make('charset')
                    ->label(__('language.label.charset'))
                    ->searchable(),
                TextColumn::make('direction')
                    ->label(__('language.label.direction'))
                    ->badge(),
                IconColumn::make('is_default')
                    ->label(__('language.label.is_default'))
                    ->size(IconSize::Medium)
                    ->alignCenter(true)
                    ->boolean()
                    ->action(function (Language $record) {
                        if (!auth()->user()->can('update', $record)) {
                            return;
                        }
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
                    ->label(__('language.label.is_active'))
                    ->size(IconSize::Medium)
                    ->alignCenter(true)
                    ->boolean()
                    ->action(function (Language $record) {
                        if (!auth()->user()->can('update', $record)) {
                            return;
                        }
                        // Varsayılan dil pasif yapılamaz
                        if ($record->is_default && $record->is_active) {
                            return;
                        }

                        // Toggle yap
                        $record->is_active = !$record->is_active;
                        $record->save();
                    }),
                TextColumn::make('created_at')
                    ->label(__('language.label.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('language.label.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::edit.single.label')),
                DeleteAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::delete.single.label'))
                    ->hidden(fn($record) => $record->is_default)
                    ->disabled(fn($record) => $record->is_default),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('filament-actions::delete.multiple.label'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->delete()),
                ]),
            ]);

    }
}
