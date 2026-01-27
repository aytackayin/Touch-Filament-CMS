<?php

namespace App\Filament\Pages;

use Jeffgreco13\FilamentBreezy\Pages\MyProfilePage;
use BackedEnum;

class BreezyProfile extends MyProfilePage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user)
            return false;

        // Super admin always has access
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check for the permission Shield generated
        return $user->can('View:BreezyProfile');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
