<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, User $pengguna): bool
    {
        return $this->isAdmin($user) && $this->isAkunYangDikelola($pengguna);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, User $pengguna): bool
    {
        return false;
    }

    public function resetPassword(User $user, User $pengguna): bool
    {
        return $this->isAdmin($user) && $this->isAkunYangDikelola($pengguna);
    }

    public function activate(User $user, User $pengguna): bool
    {
        return $this->isAdmin($user) && $this->isAkunYangDikelola($pengguna) && ! $pengguna->is_aktif;
    }

    public function deactivate(User $user, User $pengguna): bool
    {
        return $this->isAdmin($user)
            && $this->isAkunYangDikelola($pengguna)
            && $pengguna->is_aktif
            && ! $user->is($pengguna)
            && ! $pengguna->adminAktifTerakhir();
    }

    public function delete(User $user, User $pengguna): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, User $pengguna): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $pengguna): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    private function isAdmin(User $user): bool
    {
        return $user->is_aktif && $user->role === UserRole::Admin;
    }

    private function isAkunYangDikelola(User $pengguna): bool
    {
        return in_array($pengguna->role, [UserRole::Admin, UserRole::Guru], true);
    }
}
