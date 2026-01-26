<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;
use App\Filament\Exports\UserExporter;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use App\Models\User;
use Illuminate\Contracts\View\View;

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
            ->defaultPaginationPageOption($table->getLivewire()->userPreferredPerPage ?? 10)
            ->striped()
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label(__('filament-breezy::default.fields.avatar'))
                    ->circular()
                    ->disk('attachments')
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('user.label.name'))
                    ->searchable()
                    ->toggleable(false),
                TextColumn::make('email')
                    ->label(__('user.label.email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('email', $livewire->visibleColumns ?? [])),
                TextColumn::make('phone')
                    ->label(__('user.label.phone'))
                    ->searchable()
                    ->toggleable(),
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
                ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->label('')
                    ->tooltip(__('filament-actions::view.single.label'))
                    ->modalContent(fn(User $record): View => view(
                        'filament.resources.users.modals.view-profile',
                        ['record' => $record],
                    ))
                    ->form([])
                    ->infolist([])
                    ->modalWidth('xl')
                    ->modalHeading(false)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
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
