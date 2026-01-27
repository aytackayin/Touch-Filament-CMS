<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('')
                ->tooltip(__('filament-actions::delete.single.label'))
                ->modalHeading(fn() => __('user.delete_confirmation_title.user', ['name' => $this->record->name]))
                ->modalDescription(__('user.delete_confirmation_description'))
                ->color('danger')
                ->icon('heroicon-m-trash'),
        ];
    }
}
