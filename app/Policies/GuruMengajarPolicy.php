<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\GuruMengajar;
use App\Models\User;

class GuruMengajarPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, GuruMengajar $guruMengajar): bool
    {
        return $this->isAdmin($user)
            || ($user->is_aktif && $user->role === UserRole::Guru && $guruMengajar->guru?->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, GuruMengajar $guruMengajar): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, GuruMengajar $guruMengajar): bool
    {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, GuruMengajar $guruMengajar): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, GuruMengajar $guruMengajar): bool
    {
        return false;
    }

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
