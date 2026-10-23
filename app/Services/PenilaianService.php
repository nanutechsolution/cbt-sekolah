<?php

namespace App\Services;

use App\Enums\StatusPesertaUjian;
use App\Enums\TipeSoal;
use App\Models\PesertaUjian;
use App\Models\Soal;
use Illuminate\Support\Facades\DB;

class PenilaianService
{
    /**
     * Grade all PG answers and finalise the participant exactly once.
     */
    public function nilai(PesertaUjian $peserta): PesertaUjian
    {
        return DB::transaction(function () use ($peserta): PesertaUjian {
            $peserta = PesertaUjian::query()
                ->with('jadwalUjian.paketSoal')
                ->lockForUpdate()
                ->findOrFail($peserta->id);

            if ($peserta->status === StatusPesertaUjian::Selesai) {
                return $peserta;
            }

            $soal = $peserta->jadwalUjian->paketSoal->soal()->with('opsi')->get();
            $jawaban = $peserta->jawaban()->get()->keyBy('soal_id');

            $totalBobot = 0.0;
            $totalSkor = 0.0;

            foreach ($soal as $butir) {
                if ($butir->tipe === TipeSoal::Essay) {
                    continue;
                }

                $bobot = (float) $butir->bobot;
                $totalBobot += $bobot;

                $jawabanSiswa = $jawaban->get($butir->id);
                $benar = $this->periksaJawaban($butir, $jawabanSiswa?->jawaban);
                $skor = $benar ? $bobot : 0.0;
                $totalSkor += $skor;

                if ($jawabanSiswa !== null) {
                    $jawabanSiswa->update([
                        'is_benar' => $benar,
                        'skor' => $skor,
                    ]);
                }
            }

            $nilaiPg = $totalBobot > 0
                ? round($totalSkor / $totalBobot * 100, 2)
                : 0.0;

            $peserta->update([
                'status' => StatusPesertaUjian::Selesai,
                'waktu_selesai' => now(),
                'nilai_pg' => $nilaiPg,
                'nilai_essay' => null,
                'nilai' => $nilaiPg,
                'terakhir_aktif' => now(),
            ]);

            return $peserta->refresh();
        });
    }

    private function periksaJawaban(Soal $soal, mixed $jawaban): bool
    {
        return match ($soal->tipe) {
            TipeSoal::Pg, TipeSoal::BenarSalah => $this->periksaSatuOpsi($soal, $jawaban),
            TipeSoal::PgKompleks => $this->periksaBanyakOpsi($soal, $jawaban),
            TipeSoal::IsianSingkat => $this->periksaIsian($soal, $jawaban),
            TipeSoal::Menjodohkan => $this->periksaMenjodohkan($soal, $jawaban),
            TipeSoal::BenarSalahPernyataan => $this->periksaPernyataan($soal, $jawaban),
            TipeSoal::Urutan => $this->periksaUrutan($soal, $jawaban),
            TipeSoal::Essay => false,
        };
    }

    private function periksaSatuOpsi(Soal $soal, mixed $jawaban): bool
    {
        $kunci = $soal->opsi->firstWhere('is_benar', true);
        $opsiDipilih = data_get($jawaban, 'opsi_id');

        return $kunci !== null
            && $opsiDipilih !== null
            && (int) $opsiDipilih === (int) $kunci->id;
    }

    private function periksaBanyakOpsi(Soal $soal, mixed $jawaban): bool
    {
        $dipilih = collect(data_get($jawaban, 'opsi_id', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $kunci = $soal->opsi
            ->where('is_benar', true)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        return $dipilih === $kunci;
    }

    private function periksaIsian(Soal $soal, mixed $jawaban): bool
    {
        $teks = $this->normalisasiTeks(data_get($jawaban, 'teks'));

        if ($teks === '') {
            return false;
        }

        return $soal->opsi
            ->where('is_benar', true)
            ->contains(fn (mixed $opsi): bool => $this->normalisasiTeks($opsi->teks) === $teks);
    }

    private function periksaMenjodohkan(Soal $soal, mixed $jawaban): bool
    {
        $pasangan = data_get($jawaban, 'pasangan');

        if (! is_array($pasangan) || count($pasangan) !== $soal->opsi->count()) {
            return false;
        }

        foreach ($soal->opsi as $opsi) {
            if (($pasangan[$opsi->id] ?? null) === null) {
                return false;
            }

            if ($this->normalisasiTeks($pasangan[$opsi->id]) !== $this->normalisasiTeks($opsi->pasangan)) {
                return false;
            }
        }

        return true;
    }

    private function periksaPernyataan(Soal $soal, mixed $jawaban): bool
    {
        $pernyataan = data_get($jawaban, 'pernyataan');

        if (! is_array($pernyataan) || count($pernyataan) !== $soal->opsi->count()) {
            return false;
        }

        foreach ($soal->opsi as $opsi) {
            if (! array_key_exists($opsi->id, $pernyataan)) {
                return false;
            }

            if ((bool) $pernyataan[$opsi->id] !== (bool) $opsi->is_benar) {
                return false;
            }
        }

        return true;
    }

    private function periksaUrutan(Soal $soal, mixed $jawaban): bool
    {
        $dipilih = collect(data_get($jawaban, 'urutan', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        $kunci = $soal->opsi
            ->sortBy('urutan')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        return $dipilih === $kunci;
    }

    private function normalisasiTeks(mixed $teks): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $teks) ?? ''), 'UTF-8');
    }
}
