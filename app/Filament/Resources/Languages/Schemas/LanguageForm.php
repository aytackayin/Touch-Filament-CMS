<?php

namespace App\Filament\Resources\Languages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use App\Models\Language;


class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                Select::make('charset')
                    ->options([
                        'UTF-8' => 'UTF-8',
                        'ISO-8859-1' => 'ISO-8859-1',
                        'Windows-1256' => 'Windows-1256',
                    ])
                    ->required()
                    ->default('UTF-8'),

                Select::make('direction')
                    ->options([
                        'ltr' => 'LTR',
                        'rtl' => 'RTL',
                    ])
                    ->required()
                    ->default('ltr'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
