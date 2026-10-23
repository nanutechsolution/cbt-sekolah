<?php

namespace App\Services;

use App\Enums\JenisAksiLog;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LogAktivitasService
{
    /**
     * @param  array<string, mixed>|null  $dataSebelum
     * @param  array<string, mixed>|null  $dataSesudah
     */
    public function catat(
        JenisAksiLog $aksi,
        Model $entitas,
        ?User $pengguna = null,
        ?string $keterangan = null,
        ?array $dataSebelum = null,
        ?array $dataSesudah = null,
        ?string $ipAddress = null,
        ?string $penggunaNama = null,
    ): LogAktivitas {
        if ($pengguna === null) {
            $pengguna = Auth::guard('web')->user();

            if (! $pengguna instanceof User) {
                $pengguna = null;
            }
        }

        if ($ipAddress === null && ! app()->runningInConsole()) {
            $ipAddress = request()->ip();
        }

        return LogAktivitas::query()->create([
            'pengguna_id' => $pengguna?->id,
            'pengguna_nama' => $penggunaNama ?? $pengguna?->name,
            'aksi' => $aksi,
            'entitas_type' => $entitas->getMorphClass(),
            'entitas_id' => $entitas->getKey(),
            'keterangan' => $keterangan,
            'data_sebelum' => $dataSebelum,
            'data_sesudah' => $dataSesudah,
            'ip_address' => $ipAddress,
        ]);
    }
}
