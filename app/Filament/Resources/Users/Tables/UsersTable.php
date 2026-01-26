<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;
use App\Filament\Exports\UserExporter;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\User;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $isSuperAdmin = auth()->user()?->hasRole('super_admin');

        return $table
            ->modifyQueryUsing(function ($query) {
                $authUser = auth()->user();
                if (!$authUser?->hasRole('super_admin')) {
                    $query->whereDoesntHave('roles', function ($q) {
                        $q->where('name', 'super_admin');
                    });
                }
            })
            ->defaultPaginationPageOption($table->getLivewire()->perPage ?? 10)
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->label(__('user.label.name'))
                    ->searchable()
                    ->toggleable(false),
                TextColumn::make('email')
                    ->label(__('user.label.email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('email', $livewire->visibleColumns ?? [])),
                TextColumn::make('roles.name')
                    ->label(__('user.label.roles'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('roles', $livewire->visibleColumns ?? [])),
                TextColumn::make('created_at')
                    ->label(__('user.label.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('created_at', $livewire->visibleColumns ?? [])),
                TextColumn::make('updated_at')
                    ->label(__('user.label.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('updated_at', $livewire->visibleColumns ?? [])),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->label('')
                    ->tooltip(__('filament-actions::edit.single.label')),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->label('')
                    ->tooltip(__('filament-actions::delete.single.label'))
                    ->modalHeading(fn(User $record) => __('user.delete_confirmation_title.user', ['name' => $record->name]))
                    ->modalDescription(__('user.delete_confirmation_description')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible($isSuperAdmin),
                    ExportBulkAction::make()
                        ->label(__('filament-actions::export.modal.actions.export.label'))
                        ->icon(Heroicon::OutlinedArrowUpOnSquareStack)
                        ->exporter(UserExporter::class)
                        ->visible($isSuperAdmin),
                ]),
            ]);
    }
}
