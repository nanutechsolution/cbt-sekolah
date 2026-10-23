<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\GuruMengajar;
use App\Models\PaketSoal;
use App\Models\User;
use App\Services\PaketSoalLockService;
use Illuminate\Auth\Access\Response;

class PaketSoalPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, PaketSoal $paketSoal): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $this->canView($user) || $user->role !== UserRole::Guru) {
            return false;
        }

        if ($paketSoal->dibuat_oleh === $user->id) {
            return true;
        }

        $guruId = $user->guru?->id;

        if (! $guruId) {
            return false;
        }

        return GuruMengajar::query()
            ->where('guru_id', $guruId)
            ->where('mapel_id', $paketSoal->mapel_id)
            ->where('tahun_ajaran_id', $paketSoal->tahun_ajaran_id)
            ->whereHas('kelas', fn ($query) => $query->where('tingkat', $paketSoal->tingkat))
            ->exists();
    }

    public function create(User $user): bool
    {
        return $this->canView($user);
    }

    public function createForMapel(User $user, int $mapelId, int $tingkat, ?int $tahunAjaranId = null): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $this->canView($user) || $user->role !== UserRole::Guru) {
            return false;
        }

        $guruId = $user->guru?->id;

        if (! $guruId) {
            return false;
        }

        return GuruMengajar::query()
            ->where('guru_id', $guruId)
            ->where('mapel_id', $mapelId)
            ->when($tahunAjaranId, fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereHas('kelas', fn ($query) => $query->where('tingkat', $tingkat))
            ->exists();
    }

    public function update(User $user, PaketSoal $paketSoal): bool
    {
        return $this->isAdmin($user) || $paketSoal->dibuat_oleh === $user->id;
    }

    public function delete(User $user, PaketSoal $paketSoal): bool|Response
    {
        if ($paketSoal->isTerkunci()) {
            return Response::deny(PaketSoalLockService::PESAN_TERKUNCI);
        }

        return $this->isAdmin($user) || $paketSoal->dibuat_oleh === $user->id;
    }

    public function duplicate(User $user, PaketSoal $paketSoal): bool
    {
        return $this->view($user, $paketSoal);
    }

    public function createSoal(User $user, PaketSoal $paketSoal): bool|Response
    {
        if ($paketSoal->isTerkunci()) {
            return Response::deny(PaketSoalLockService::PESAN_TERKUNCI);
        }

        return $user->can('update', $paketSoal);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, PaketSoal $paketSoal): bool
    {
        return $this->isAdmin($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, PaketSoal $paketSoal): bool
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
