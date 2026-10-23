<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LogAktivitas;
use App\Models\User;

class LogAktivitasPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, LogAktivitas $logAktivitas): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, LogAktivitas $logAktivitas): bool
    {
        return false;
    }

    public function delete(User $user, LogAktivitas $logAktivitas): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function isAdmin(User $user): bool
    {
        return $user->is_aktif && $user->role === UserRole::Admin;
    }
}
