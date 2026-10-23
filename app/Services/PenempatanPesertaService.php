<?php

namespace App\Services;

use App\Models\JenisUjian;
use App\Models\Kelas;
use App\Models\PenempatanPeserta;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PenempatanPesertaService
{
    /**
     * Place every active student in a class for the selected year and exam type.
     *
     * @param  array{tahun_ajaran_id: int|string, jenis_ujian_id: int|string, kelas_id: int|string, ruang_id: int|string, sesi_id: int|string, awalan_no_peserta: string}  $data
     */
    public function tempatkan(User $user, array $data): PenempatanPesertaResult
    {
        Gate::forUser($user)->authorize('tempatkan', PenempatanPeserta::class);

        $tahunAjaranId = (int) $data['tahun_ajaran_id'];
        $jenisUjianId = (int) $data['jenis_ujian_id'];
        $kelasId = (int) $data['kelas_id'];
        $ruangId = (int) $data['ruang_id'];
        $sesiId = (int) $data['sesi_id'];
        $awalan = trim((string) $data['awalan_no_peserta']);

        return DB::transaction(function () use (
            $tahunAjaranId,
            $jenisUjianId,
            $kelasId,
            $ruangId,
            $sesiId,
            $awalan,
        ): PenempatanPesertaResult {
            TahunAjaran::query()->lockForUpdate()->findOrFail($tahunAjaranId);
            JenisUjian::query()->findOrFail($jenisUjianId);
            Kelas::query()->findOrFail($kelasId);
            $ruang = Ruang::query()->findOrFail($ruangId);
            $sesi = Sesi::query()->findOrFail($sesiId);

            $siswaIds = Siswa::query()
                ->where('kelas_id', $kelasId)
                ->where('is_aktif', true)
                ->orderBy('nama')
                ->orderBy('id')
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id);

            $penempatanLama = PenempatanPeserta::query()
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('jenis_ujian_id', $jenisUjianId)
                ->get(['siswa_id', 'no_peserta']);

            $siswaSudahDitempatkan = $penempatanLama
                ->pluck('siswa_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $siswaBaru = $siswaIds
                ->reject(fn (int $id): bool => in_array($id, $siswaSudahDitempatkan, true))
                ->values();

            $urutanTerakhir = $this->urutanTerakhir(
                $penempatanLama->pluck('no_peserta'),
                $awalan,
            );

            $nomorAwal = null;
            $nomorAkhir = null;

            foreach ($siswaBaru as $siswaId) {
                $urutanTerakhir++;
                $nomor = $this->formatNomorPeserta($awalan, $urutanTerakhir);

                PenempatanPeserta::query()->create([
                    'siswa_id' => $siswaId,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'jenis_ujian_id' => $jenisUjianId,
                    'ruang_id' => $ruangId,
                    'sesi_id' => $sesiId,
                    'no_peserta' => $nomor,
                ]);

                $nomorAwal ??= $nomor;
                $nomorAkhir = $nomor;
            }

            $jumlahPesertaRuangSesi = PenempatanPeserta::query()
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('jenis_ujian_id', $jenisUjianId)
                ->where('ruang_id', $ruangId)
                ->where('sesi_id', $sesiId)
                ->count();

            return new PenempatanPesertaResult(
                dibuat: $siswaBaru->count(),
                dilewati: $siswaIds->count() - $siswaBaru->count(),
                nomorAwal: $nomorAwal,
                nomorAkhir: $nomorAkhir,
                jumlahPesertaRuangSesi: $jumlahPesertaRuangSesi,
                kapasitasRuang: $ruang->kapasitas,
                namaRuang: $ruang->nama,
                namaSesi: $sesi->nama,
            );
        });
    }

    public function formatNomorPeserta(string $awalan, int $urutan): string
    {
        return trim($awalan).str_pad((string) $urutan, 3, '0', STR_PAD_LEFT);
    }

    /**
     * @param  Collection<int, string>  $nomorPeserta
     */
    private function urutanTerakhir(Collection $nomorPeserta, string $awalan): int
    {
        return $nomorPeserta->reduce(function (int $tertinggi, string $nomor) use ($awalan): int {
            if (! str_starts_with($nomor, $awalan)) {
                return $tertinggi;
            }

            $urutan = substr($nomor, strlen($awalan));

            return ctype_digit($urutan)
                ? max($tertinggi, (int) $urutan)
                : $tertinggi;
        }, 0);
    }
}
