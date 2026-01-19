<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getHeading(): string
    {
        return __('user.head.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('')
                ->tooltip(__('button.new'))
                ->color('success')
                ->size('xs')
                ->icon('heroicon-m-user-plus'),
        ];
    }
}
