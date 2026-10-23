<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PesertaUjian;
use App\Models\User;

class PesertaUjianPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, PesertaUjian $pesertaUjian): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->canView($user)
            && $pesertaUjian->jadwalUjian?->paketSoal !== null
            && $user->can('view', $pesertaUjian->jadwalUjian->paketSoal);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, PesertaUjian $pesertaUjian): bool
    {
        return $this->view($user, $pesertaUjian);
    }

    public function reset(User $user, PesertaUjian $pesertaUjian): bool
    {
        return $this->isAdmin($user);
    }

    public function tambahWaktu(User $user, PesertaUjian $pesertaUjian): bool
    {
        return $this->isAdmin($user);
    }

    public function bukaKunciDevice(User $user, PesertaUjian $pesertaUjian): bool
    {
        return $this->isAdmin($user);
    }

    public function selesaikanManual(User $user, PesertaUjian $pesertaUjian): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, PesertaUjian $pesertaUjian): bool
    {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, PesertaUjian $pesertaUjian): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, PesertaUjian $pesertaUjian): bool
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
