<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Filament\Resources\Languages\LanguageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLanguages extends ListRecords
{
    protected static string $resource = LanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('')
                ->tooltip(__('button.new'))
                ->color('success')
                ->size('xs')
                ->icon('heroicon-m-language'),
        ];
    }
}
