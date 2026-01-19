<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TouchFile;
use Illuminate\Auth\Access\HandlesAuthorization;

class TouchFilePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TouchFile');
    }

    public function view(AuthUser $authUser, TouchFile $touchFile): bool
    {
        return $authUser->can('View:TouchFile');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TouchFile');
    }

    public function update(AuthUser $authUser, TouchFile $touchFile): bool
    {
        return $authUser->can('Update:TouchFile');
    }

    public function delete(AuthUser $authUser, TouchFile $touchFile): bool
    {
        return $authUser->can('Delete:TouchFile');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TouchFile');
    }

    public function restore(AuthUser $authUser, TouchFile $touchFile): bool
    {
        return $authUser->can('Restore:TouchFile');
    }

    public function forceDelete(AuthUser $authUser, TouchFile $touchFile): bool
    {
        return $authUser->can('ForceDelete:TouchFile');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TouchFile');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TouchFile');
    }

    public function replicate(AuthUser $authUser, TouchFile $touchFile): bool
    {
        return $authUser->can('Replicate:TouchFile');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TouchFile');
    }

}