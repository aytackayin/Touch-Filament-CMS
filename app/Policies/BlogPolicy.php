<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Blog;
use Illuminate\Auth\Access\HandlesAuthorization;

class BlogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Blog');
    }

    public function view(AuthUser $authUser, Blog $blog): bool
    {
        return $authUser->can('View:Blog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Blog');
    }

    public function update(AuthUser $authUser, Blog $blog): bool
    {
        return $authUser->can('Update:Blog');
    }

    public function delete(AuthUser $authUser, Blog $blog): bool
    {
        return $authUser->can('Delete:Blog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Blog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Blog');
    }

    public function import(AuthUser $authUser): bool
    {
        return $authUser->can('Import:Blog');
    }

    public function export(AuthUser $authUser): bool
    {
        return $authUser->can('Export:Blog');
    }

}