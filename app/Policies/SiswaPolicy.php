<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Siswa;
use App\Models\User;

class SiswaPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, Siswa $siswa): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $this->canView($user) || $user->role !== UserRole::Guru) {
            return false;
        }

        return $siswa->kelas
            ?->guruMengajar()
            ->whereHas('guru', fn ($query) => $query->where('user_id', $user->id))
            ->exists() === true
            || $siswa->kelas?->wali_kelas_id === $user->guru?->id;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Siswa $siswa): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Siswa $siswa): bool
    {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Siswa $siswa): bool
    {
        return $this->isAdmin($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Siswa $siswa): bool
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
