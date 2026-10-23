<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Soal;
use App\Models\User;
use App\Services\PaketSoalLockService;
use Illuminate\Auth\Access\Response;

class SoalPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, Soal $soal): bool
    {
        $paket = $soal->paketSoal;

        return $paket !== null && $user->can('view', $paket);
    }

    public function create(User $user): bool
    {
        return $this->canView($user);
    }

    public function update(User $user, Soal $soal): bool|Response
    {
        $paket = $soal->paketSoal;

        if ($paket === null) {
            return false;
        }

        if ($paket->isTerkunci()) {
            return Response::deny(PaketSoalLockService::PESAN_TERKUNCI);
        }

        return $user->can('update', $paket);
    }

    public function delete(User $user, Soal $soal): bool|Response
    {
        $paket = $soal->paketSoal;

        if ($paket === null) {
            return false;
        }

        if ($paket->isTerkunci()) {
            return Response::deny(PaketSoalLockService::PESAN_TERKUNCI);
        }

        return $user->can('update', $paket);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Soal $soal): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Soal $soal): bool
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
