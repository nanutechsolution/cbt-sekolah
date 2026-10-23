<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PengaturanSekolah;
use App\Models\User;

class PengaturanSekolahPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PengaturanSekolah $pengaturanSekolah): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PengaturanSekolah $pengaturanSekolah): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PengaturanSekolah $pengaturanSekolah): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PengaturanSekolah $pengaturanSekolah): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PengaturanSekolah $pengaturanSekolah): bool
    {
        return false;
    }

    private function isAdmin(User $user): bool
    {
        return $user->is_aktif && $user->role === UserRole::Admin;
    }
}
