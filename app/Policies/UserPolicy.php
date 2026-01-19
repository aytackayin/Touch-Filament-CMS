<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:User');
    }

    public function view(AuthUser $authUser, User $model): bool
    {
        /** @var \App\Models\User $authUser */
        if ($model->hasRole('super_admin') && !$authUser->hasRole('super_admin')) {
            return false;
        }
        return $authUser->can('View:User');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:User');
    }

    public function update(AuthUser $authUser, User $model): bool
    {
        /** @var \App\Models\User $authUser */
        // Kendini düzenleyebilir (şifre vs. için)
        if ($authUser->id === $model->id) {
            return $authUser->can('Update:User');
        }

        // Super Admin her şeyi yapabilir
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        // Super Admin düzenlenemez
        if ($model->hasRole('super_admin')) {
            return false;
        }

        // Dinamik Hiyerarşi Kontrolü:
        // Hedef kullanıcının sahip olduğu HERHANGİ BİR izin, düzenleyen kişide YOKSA işlemi engelle.
        // Bu sayede "Writer", "Admin"i düzenleyemez çünkü Admin'in izinleri Writer'da yoktur.
        $targetPermissions = $model->getAllPermissions()->pluck('id');
        $myPermissions = $authUser->getAllPermissions()->pluck('id');

        if ($targetPermissions->diff($myPermissions)->isNotEmpty()) {
            return false;
        }

        return $authUser->can('Update:User');
    }

    public function delete(AuthUser $authUser, User $model): bool
    {
        /** @var \App\Models\User $authUser */
        // Kimse kendini silemez
        if ($authUser->id === $model->id) {
            return false;
        }

        // Super Admin her şeyi yapabilir
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        // Super Admin silinemez
        if ($model->hasRole('super_admin')) {
            return false;
        }

        // Dinamik Hiyerarşi Kontrolü (Silme için de aynı mantık)
        $targetPermissions = $model->getAllPermissions()->pluck('id');
        $myPermissions = $authUser->getAllPermissions()->pluck('id');

        if ($targetPermissions->diff($myPermissions)->isNotEmpty()) {
            return false;
        }

        return $authUser->can('Delete:User');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:User');
    }

    public function restore(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('Restore:User');
    }

    public function forceDelete(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('ForceDelete:User');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:User');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:User');
    }

    public function replicate(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('Replicate:User');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:User');
    }

}