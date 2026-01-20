<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Blog;
use Illuminate\Auth\Access\HandlesAuthorization;

class BlogPolicy
{
    use HandlesAuthorization;

    public function before(AuthUser $authUser, string $ability): ?bool
    {
        if ($authUser->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return null;
    }

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
        return $authUser->can('Update:Blog') && $authUser->id === $blog->user_id;
    }

    public function delete(AuthUser $authUser, Blog $blog): bool
    {
        return $authUser->can('Delete:Blog') && $authUser->id === $blog->user_id;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Blog');
    }

    public function restore(AuthUser $authUser, Blog $blog): bool
    {
        return $authUser->can('Restore:Blog') && $authUser->id === $blog->user_id;
    }

    public function forceDelete(AuthUser $authUser, Blog $blog): bool
    {
        return $authUser->can('ForceDelete:Blog') && $authUser->id === $blog->user_id;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Blog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Blog');
    }

    public function replicate(AuthUser $authUser, Blog $blog): bool
    {
        return $authUser->can('Replicate:Blog') && $authUser->id === $blog->user_id;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Blog');
    }

}