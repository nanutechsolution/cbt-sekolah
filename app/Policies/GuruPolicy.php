<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Guru;
use App\Models\User;

class GuruPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Guru $guru): bool
    {
        return $this->isAdmin($user)
            || ($user->is_aktif && $user->role === UserRole::Guru && $guru->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Guru $guru): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Guru $guru): bool
    {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Guru $guru): bool
    {
        return $this->isAdmin($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Guru $guru): bool
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
