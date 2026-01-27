<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BlogCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class BlogCategoryPolicy
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
        return $authUser->can('ViewAny:BlogCategory');
    }

    public function view(AuthUser $authUser, BlogCategory $blogCategory): bool
    {
        return $authUser->can('View:BlogCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BlogCategory');
    }

    public function update(AuthUser $authUser, BlogCategory $blogCategory): bool
    {
        return $authUser->can('Update:BlogCategory') && $authUser->id === $blogCategory->user_id;
    }

    public function delete(AuthUser $authUser, BlogCategory $blogCategory): bool
    {
        return $authUser->can('Delete:BlogCategory') && $authUser->id === $blogCategory->user_id;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BlogCategory');
    }

    public function restore(AuthUser $authUser, BlogCategory $blogCategory): bool
    {
        return $authUser->can('Restore:BlogCategory') && $authUser->id === $blogCategory->user_id;
    }

    public function forceDelete(AuthUser $authUser, BlogCategory $blogCategory): bool
    {
        return $authUser->can('ForceDelete:BlogCategory') && $authUser->id === $blogCategory->user_id;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BlogCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BlogCategory');
    }

    public function replicate(AuthUser $authUser, BlogCategory $blogCategory): bool
    {
        return $authUser->can('Replicate:BlogCategory') && $authUser->id === $blogCategory->user_id;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BlogCategory');
    }

    public function import(AuthUser $authUser): bool
    {
        return $authUser->can('Import:BlogCategory');
    }

    public function export(AuthUser $authUser): bool
    {
        return $authUser->can('Export:BlogCategory');
    }
}