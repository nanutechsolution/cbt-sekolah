<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PenempatanPeserta;
use App\Models\User;

class PenempatanPesertaPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, PenempatanPeserta $penempatanPeserta): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, PenempatanPeserta $penempatanPeserta): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, PenempatanPeserta $penempatanPeserta): bool
    {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function tempatkan(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, PenempatanPeserta $penempatanPeserta): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, PenempatanPeserta $penempatanPeserta): bool
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
}
