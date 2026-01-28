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

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TouchFile');
    }

    public function sync(AuthUser $authUser): bool
    {
        return $authUser->can('Sync:TouchFile');
    }

}