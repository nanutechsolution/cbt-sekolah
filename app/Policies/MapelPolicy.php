<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Mapel;
use App\Models\User;

class MapelPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, Mapel $mapel): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $this->canView($user) || $user->role !== UserRole::Guru) {
            return false;
        }

        return $mapel->guruMengajar()->whereHas(
            'guru',
            fn ($query) => $query->where('user_id', $user->id),
        )->exists();
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Mapel $mapel): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Mapel $mapel): bool
    {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Mapel $mapel): bool
    {
        return $this->isAdmin($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Mapel $mapel): bool
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
