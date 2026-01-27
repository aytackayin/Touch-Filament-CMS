<?php

namespace App\Filament\Resources\Users;

use App\Models\User;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getNavigationIcon(): string
    {
        return __('user.nav.icon');
    }

    public static function getNavigationSort(): ?int
    {
        return 300;
    }

    public static function getNavigationLabel(): string
    {
        return __('user.nav.label');
    }
    public static function getBreadcrumb(): string
    {
        return __('user.nav.label');
    }

    public static function getModelLabel(): string
    {
        return __('user.label.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('user.label.users');
    }

    protected static ?string $recordTitleAttribute = 'name';
    public static function getNavigationGroup(): ?string
    {
        return __('user.nav.group');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
