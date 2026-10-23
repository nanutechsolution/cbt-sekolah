<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\TahunAjaran;
use App\Models\User;

class TahunAjaranPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TahunAjaran $tahunAjaran): bool
    {
        return $this->canView($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TahunAjaran $tahunAjaran): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TahunAjaran $tahunAjaran): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can delete multiple models.
     */
    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TahunAjaran $tahunAjaran): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore multiple models.
     */
    public function restoreAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TahunAjaran $tahunAjaran): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete multiple models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    private function canView(User $user): bool
    {
        return $user->is_aktif && in_array($user->role, [UserRole::Admin, UserRole::Guru], true);
    }

    private function isAdmin(User $user): bool
    {
        return $user->is_aktif && $user->role === UserRole::Admin;
    }
}
