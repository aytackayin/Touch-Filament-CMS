<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('label.name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('label.email'))
                    ->email()
                    ->required(),

                TextInput::make('password')
                    ->label(__('label.password'))
                    ->password()
                    ->confirmed()
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create'),
                TextInput::make('password_confirmation')
                    ->label(__('label.password_confirmation'))
                    ->required(fn(string $context): bool => $context === 'create')
                    ->password()
                    ->dehydrated(false),

                Select::make('roles')
                    ->label(__('label.roles'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->multiple()
                    ->relationship('roles', 'name'),
            ]);
    }
}
