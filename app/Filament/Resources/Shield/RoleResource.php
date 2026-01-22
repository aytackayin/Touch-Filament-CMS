<?php

namespace App\Filament\Resources\Shield;

use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as BaseRoleResource;
use Illuminate\Support\Collection;

class RoleResource extends BaseRoleResource
{
    protected static ?string $recordTitleAttribute = null;

    protected static bool $isGloballySearchable = false;

    public static function getGlobalSearchResults(string $search): Collection
    {
        return collect();
    }

    public static function canGloballySearch(): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
