<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Kelas;
use App\Models\User;

class KelasPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, Kelas $kelas): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $this->canView($user) || $user->role !== UserRole::Guru) {
            return false;
        }

        return $kelas->wali_kelas_id === $this->guruId($user)
            || $kelas->guruMengajar()->whereHas(
                'guru',
                fn ($query) => $query->where('user_id', $user->id),
            )->exists();
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Kelas $kelas): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Kelas $kelas): bool
    {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Kelas $kelas): bool
    {
        return $this->isAdmin($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Kelas $kelas): bool
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

    private function guruId(User $user): ?int
    {
        return $user->guru?->id;
    }
}
