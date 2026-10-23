<?php

namespace App\Services;

use App\Enums\StatusJadwalUjian;
use App\Enums\StatusPaketSoal;
use App\Enums\UserRole;
use App\Models\GuruMengajar;
use App\Models\JadwalUjian;
use App\Models\Kelas;
use App\Models\PaketSoal;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JadwalUjianService
{
    /**
     * Ensure a user may schedule the selected package and classes.
     *
     * @param  array<string, mixed>  $data
     */
    public function pastikanBolehMenyimpan(User $user, array $data): void
    {
        $paket = PaketSoal::query()->find($data['paket_soal_id']);

        if ($paket === null) {
            throw ValidationException::withMessages([
                'paket_soal_id' => 'Paket soal tidak ditemukan.',
            ]);
        }

        if (! $user->can('view', $paket)) {
            throw new AuthorizationException('Anda tidak berhak menggunakan paket soal ini.');
        }

        if ($user->role === UserRole::Admin) {
            return;
        }

        $guruId = $user->guru?->id;
        $kelasIds = collect($data['kelas'] ?? [])->map(fn (mixed $id): int => (int) $id)->unique();

        if ($guruId === null || $kelasIds->isEmpty()) {
            throw new AuthorizationException('Anda tidak berhak menggunakan kelas ini.');
        }

        $jumlahKelasDiampu = GuruMengajar::query()
            ->where('guru_id', $guruId)
            ->where('mapel_id', $paket->mapel_id)
            ->where('tahun_ajaran_id', $paket->tahun_ajaran_id)
            ->whereIn('kelas_id', $kelasIds)
            ->distinct()
            ->count('kelas_id');

        if ($jumlahKelasDiampu !== $kelasIds->count()) {
            throw new AuthorizationException('Anda hanya boleh memilih kelas yang Anda ampu untuk mapel ini.');
        }
    }

    /**
     * Create or update a schedule and synchronise its classes.
     *
     * @param  array<string, mixed>  $data
     */
    public function simpan(array $data, ?JadwalUjian $jadwal = null): JadwalUjian
    {
        $paket = PaketSoal::query()->findOrFail($data['paket_soal_id']);

        $this->pastikanPaketSiap($paket);

        $mulai = Carbon::parse($data['mulai']);
        $selesai = Carbon::parse($data['selesai']);
        $durasi = (int) $data['durasi_menit'];
        $kelasIds = collect($data['kelas'] ?? [])->map(fn (mixed $id): int => (int) $id)->unique()->values();

        if ($mulai->greaterThanOrEqualTo($selesai)) {
            throw ValidationException::withMessages([
                'mulai' => 'Waktu mulai harus lebih awal daripada waktu selesai.',
            ]);
        }

        if ($jadwal === null && $mulai->lessThan(now()->startOfMinute())) {
            throw ValidationException::withMessages([
                'mulai' => 'Waktu mulai tidak boleh di masa lalu saat membuat jadwal baru.',
            ]);
        }

        $menitJendela = intdiv($selesai->getTimestamp() - $mulai->getTimestamp(), 60);

        if ($menitJendela < $durasi) {
            throw ValidationException::withMessages([
                'selesai' => "Jendela ujian hanya {$menitJendela} menit, lebih pendek dari durasi {$durasi} menit. Perpanjang waktu selesai atau kurangi durasi.",
            ]);
        }

        if ($durasi < 1) {
            throw ValidationException::withMessages([
                'durasi_menit' => 'Durasi ujian minimal 1 menit.',
            ]);
        }

        if ($kelasIds->isEmpty()) {
            throw ValidationException::withMessages([
                'kelas' => 'Pilih minimal satu kelas peserta.',
            ]);
        }

        $jumlahKelas = Kelas::query()
            ->whereIn('id', $kelasIds)
            ->where('tingkat', $paket->tingkat)
            ->count();

        if ($jumlahKelas !== $kelasIds->count()) {
            throw ValidationException::withMessages([
                'kelas' => 'Semua kelas harus sesuai dengan tingkat paket soal.',
            ]);
        }

        $this->pastikanTidakBentrok($mulai, $selesai, $kelasIds->all(), $jadwal?->id);

        return DB::transaction(function () use ($data, $jadwal, $paket, $mulai, $selesai, $durasi, $kelasIds): JadwalUjian {
            $atribut = [
                'paket_soal_id' => $paket->id,
                'nama' => trim((string) $data['nama']),
                'mulai' => $mulai,
                'selesai' => $selesai,
                'durasi_menit' => $durasi,
            ];

            if ($jadwal === null) {
                $atribut['status'] = StatusJadwalUjian::Nonaktif;
                $atribut['dibuat_oleh'] = $data['dibuat_oleh'] ?? auth()->id();
                $jadwal = JadwalUjian::query()->create($atribut);
            } else {
                $jadwal->update($atribut);
            }

            $jadwal->kelas()->sync($kelasIds->all());

            return $jadwal->refresh()->load('kelas', 'paketSoal');
        });
    }

    public function aktifkan(JadwalUjian $jadwal): JadwalUjian
    {
        $jadwal->load('paketSoal', 'kelas');

        $this->pastikanPaketSiap($jadwal->paketSoal);

        if ($jadwal->status === StatusJadwalUjian::Selesai) {
            throw ValidationException::withMessages([
                'status' => 'Jadwal yang sudah selesai tidak dapat diaktifkan kembali.',
            ]);
        }

        if ($jadwal->kelas->isEmpty()) {
            throw ValidationException::withMessages([
                'kelas' => 'Jadwal belum memiliki kelas peserta.',
            ]);
        }

        if ($jadwal->mulai->greaterThanOrEqualTo($jadwal->selesai)) {
            throw ValidationException::withMessages([
                'selesai' => 'Waktu selesai harus setelah waktu mulai.',
            ]);
        }

        $this->pastikanTidakBentrok(
            $jadwal->mulai,
            $jadwal->selesai,
            $jadwal->kelas->pluck('id')->all(),
            $jadwal->id,
        );

        $jadwal->update(['status' => StatusJadwalUjian::Aktif]);

        return $jadwal->refresh();
    }

    public function selesaikan(JadwalUjian $jadwal): JadwalUjian
    {
        if ($jadwal->status === StatusJadwalUjian::Selesai) {
            return $jadwal;
        }

        $jadwal->update(['status' => StatusJadwalUjian::Selesai]);

        return $jadwal->refresh();
    }

    public function siswaBolehMengerjakan(JadwalUjian $jadwal, int $kelasId): bool
    {
        return $jadwal->bisaDimulai()
            && $jadwal->kelas()->whereKey($kelasId)->exists();
    }

    private function pastikanPaketSiap(PaketSoal $paket): void
    {
        if ($paket->status !== StatusPaketSoal::Siap) {
            throw ValidationException::withMessages([
                'paket_soal_id' => 'Paket soal harus berstatus siap sebelum dijadwalkan.',
            ]);
        }

        if (! $paket->soal()->exists()) {
            throw ValidationException::withMessages([
                'paket_soal_id' => 'Paket soal belum memiliki soal.',
            ]);
        }
    }

    /**
     * @param  array<int, int>  $kelasIds
     */
    private function pastikanTidakBentrok(
        Carbon $mulai,
        Carbon $selesai,
        array $kelasIds,
        ?int $kecualiId = null,
    ): void {
        $bentrok = JadwalUjian::query()
            ->when($kecualiId, fn ($query) => $query->whereKeyNot($kecualiId))
            ->where('status', '!=', StatusJadwalUjian::Selesai->value)
            ->whereHas('kelas', fn ($query) => $query->whereIn('kelas.id', $kelasIds))
            ->where('mulai', '<', $selesai)
            ->where('selesai', '>', $mulai)
            ->exists();

        if ($bentrok) {
            throw ValidationException::withMessages([
                'mulai' => 'Jadwal bentrok dengan ujian lain untuk kelas yang sama.',
            ]);
        }
    }
}
