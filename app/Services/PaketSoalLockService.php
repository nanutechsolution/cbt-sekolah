<?php

namespace App\Services;

use App\Enums\StatusJadwalUjian;
use App\Enums\StatusPesertaUjian;
use App\Models\PaketSoal;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class PaketSoalLockService
{
    public const PESAN_TERKUNCI = 'Paket ini sudah dipakai ujian dan tidak bisa diubah. Gunakan Duplikat paket soal.';

    /**
     * @var array<int, string>
     */
    public const FIELD_PAKET_TERKUNCI = [
        'mapel_id',
        'jenis_ujian_id',
        'tahun_ajaran_id',
        'tingkat',
        'acak_soal',
        'acak_opsi',
        'tampilkan_nilai',
        'status',
    ];

    /**
     * @var array<int, bool>
     */
    private array $cache = [];

    /**
     * @var array<int, int|null>
     */
    private array $paketPerSoal = [];

    public function isTerkunci(PaketSoal|int $paket): bool
    {
        $paketId = $paket instanceof PaketSoal ? $paket->getKey() : $paket;

        if (! $paketId) {
            return false;
        }

        return $this->cache[$paketId] ??= $this->hitungTerkunci($paketId);
    }

    public function pastikanTidakTerkunci(PaketSoal|int $paket, string $field = 'paket_soal'): void
    {
        if (! $this->isTerkunci($paket)) {
            return;
        }

        throw ValidationException::withMessages([
            $field => self::PESAN_TERKUNCI,
        ]);
    }

    public function pastikanSoalTidakTerkunci(int $soalId): void
    {
        $paketId = $this->paketIdUntukSoal($soalId);

        if ($paketId !== null) {
            $this->pastikanTidakTerkunci($paketId);
        }
    }

    public function paketIdUntukSoal(int $soalId): ?int
    {
        if (array_key_exists($soalId, $this->paketPerSoal)) {
            return $this->paketPerSoal[$soalId];
        }

        $paketId = Soal::query()
            ->whereKey($soalId)
            ->value('paket_soal_id');

        $this->paketPerSoal[$soalId] = $paketId !== null ? (int) $paketId : null;

        return $this->paketPerSoal[$soalId];
    }

    public function pastikanPerubahanPaketDiizinkan(PaketSoal $paket): void
    {
        $field = $this->fieldTerkunciYangBerubah($paket);

        if ($field === null || ! $this->isTerkunci($paket)) {
            return;
        }

        throw ValidationException::withMessages([
            $field => self::PESAN_TERKUNCI,
        ]);
    }

    public function lupakan(PaketSoal|int $paket): void
    {
        $paketId = $paket instanceof PaketSoal ? $paket->getKey() : $paket;

        if ($paketId) {
            unset($this->cache[$paketId]);
            $this->paketPerSoal = [];
        }
    }

    public function lupakanSoal(int $soalId): void
    {
        unset($this->paketPerSoal[$soalId]);
    }

    public function fieldTerkunciYangBerubah(PaketSoal $paket): ?string
    {
        foreach (self::FIELD_PAKET_TERKUNCI as $field) {
            if ($paket->isDirty($field)) {
                return $field;
            }
        }

        return null;
    }

    private function hitungTerkunci(int $paketId): bool
    {
        return PaketSoal::query()
            ->whereKey($paketId)
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('jadwalUjian', fn (Builder $jadwal): Builder => $jadwal
                        ->where('status', StatusJadwalUjian::Aktif->value))
                    ->orWhereHas('jadwalUjian.pesertaUjian', fn (Builder $peserta): Builder => $peserta
                        ->whereIn('status', [
                            StatusPesertaUjian::Mengerjakan->value,
                            StatusPesertaUjian::Selesai->value,
                            StatusPesertaUjian::Ditangguhkan->value,
                        ]));
            })
            ->exists();
    }
}
