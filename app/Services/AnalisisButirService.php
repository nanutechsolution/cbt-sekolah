<?php

namespace App\Services;

use App\Enums\StatusPesertaUjian;
use App\Enums\TipeSoal;
use App\Models\JadwalUjian;
use App\Models\JawabanSiswa;
use App\Models\PesertaUjian;
use App\Models\Soal;
use Illuminate\Support\Collection;

class AnalisisButirService
{
    /**
     * Build item analysis for all questions in a schedule.
     *
     * @return array{
     *     ringkasan: array{jumlah_peserta: int, jumlah_selesai: int, rata_rata: float|null, tertinggi: float|null, terendah: float|null},
     *     butir: array<int, array<string, mixed>>
     * }
     */
    public function analisis(JadwalUjian $jadwal): array
    {
        $peserta = PesertaUjian::query()
            ->where('jadwal_ujian_id', $jadwal->id)
            ->where('status', StatusPesertaUjian::Selesai->value)
            ->orderByDesc('nilai')
            ->get();

        $soal = $jadwal->paketSoal?->soal()->with('opsi')->orderBy('nomor')->get() ?? collect();

        $jawaban = JawabanSiswa::query()
            ->whereIn('peserta_ujian_id', $peserta->pluck('id'))
            ->whereIn('soal_id', $soal->pluck('id'))
            ->get()
            ->keyBy(fn (JawabanSiswa $item): string => $item->peserta_ujian_id.':'.$item->soal_id);

        $nilai = $peserta->pluck('nilai')->filter(fn ($value): bool => $value !== null)->map(fn ($value): float => (float) $value);

        return [
            'ringkasan' => [
                'jumlah_peserta' => $peserta->count(),
                'jumlah_selesai' => $peserta->count(),
                'rata_rata' => $nilai->isEmpty() ? null : round((float) $nilai->avg(), 2),
                'tertinggi' => $nilai->isEmpty() ? null : round((float) $nilai->max(), 2),
                'terendah' => $nilai->isEmpty() ? null : round((float) $nilai->min(), 2),
            ],
            'butir' => $soal->map(fn (Soal $item): array => $this->analisisSoal($item, $peserta, $jawaban))->all(),
        ];
    }

    /**
     * @param  Collection<int, PesertaUjian>  $peserta
     * @param  Collection<string, JawabanSiswa>  $jawaban
     * @return array<string, mixed>
     */
    private function analisisSoal(Soal $soal, Collection $peserta, Collection $jawaban): array
    {
        $jumlahPeserta = $peserta->count();
        $jawabanSoal = $peserta->map(fn (PesertaUjian $item): ?JawabanSiswa => $jawaban->get($item->id.':'.$soal->id));

        if ($soal->tipe === TipeSoal::Essay) {
            return [
                'soal_id' => $soal->id,
                'nomor' => $soal->nomor,
                'tipe' => $soal->tipe->value,
                'tipe_label' => $soal->tipe->getLabel(),
                'pertanyaan' => $soal->pertanyaan,
                'bobot' => (float) $soal->bobot,
                'jumlah_menjawab' => $jawabanSoal->filter(fn (?JawabanSiswa $item): bool => filled(data_get($item?->jawaban, 'teks')))->count(),
                'jumlah_benar' => null,
                'tingkat_kesukaran' => null,
                'kategori_kesukaran' => '-',
                'daya_beda' => null,
                'kategori_daya_beda' => '-',
                'opsi' => [],
            ];
        }

        if ($soal->tipe !== TipeSoal::Pg) {
            return $this->analisisSoalObjektif($soal, $peserta, $jawaban);
        }

        $opsi = $soal->opsi;
        $kunci = $opsi->firstWhere('is_benar', true);
        $benar = 0;
        $menjawab = 0;
        $jumlahOpsi = [];

        foreach ($opsi as $pilihan) {
            $jumlahOpsi[$pilihan->id] = [
                'id' => $pilihan->id,
                'label' => $pilihan->label,
                'teks' => $pilihan->teks,
                'is_benar' => (bool) $pilihan->is_benar,
                'dipilih' => 0,
                'atas' => 0,
                'bawah' => 0,
            ];
        }

        $kelompokAtas = max(1, (int) ceil($jumlahPeserta * 0.27));
        $kelompokBawah = max(1, (int) ceil($jumlahPeserta * 0.27));
        $idAtas = $peserta->take($kelompokAtas)->pluck('id')->all();
        $idBawah = $peserta->reverse()->take($kelompokBawah)->pluck('id')->all();

        $benarAtas = 0;
        $benarBawah = 0;

        foreach ($peserta as $item) {
            $jawabanItem = $jawaban->get($item->id.':'.$soal->id);
            $opsiId = data_get($jawabanItem?->jawaban, 'opsi_id');
            $opsiId = $opsiId !== null ? (int) $opsiId : null;

            if ($opsiId === null) {
                continue;
            }

            $menjawab++;

            if (isset($jumlahOpsi[$opsiId])) {
                $jumlahOpsi[$opsiId]['dipilih']++;
            }

            $isBenar = $kunci !== null && $opsiId === (int) $kunci->id;

            if ($isBenar) {
                $benar++;
            }

            if (in_array($item->id, $idAtas, true)) {
                if (isset($jumlahOpsi[$opsiId])) {
                    $jumlahOpsi[$opsiId]['atas']++;
                }
                if ($isBenar) {
                    $benarAtas++;
                }
            }

            if (in_array($item->id, $idBawah, true)) {
                if (isset($jumlahOpsi[$opsiId])) {
                    $jumlahOpsi[$opsiId]['bawah']++;
                }
                if ($isBenar) {
                    $benarBawah++;
                }
            }
        }

        $tingkatKesukaran = $jumlahPeserta > 0 ? round($benar / $jumlahPeserta, 4) : null;
        $dayaBeda = $jumlahPeserta >= 2
            ? round(($benarAtas / $kelompokAtas) - ($benarBawah / $kelompokBawah), 4)
            : null;

        $daftarOpsi = [];

        foreach ($jumlahOpsi as $pilihan) {
            $persentase = $jumlahPeserta > 0 ? round($pilihan['dipilih'] / $jumlahPeserta * 100, 2) : 0.0;

            $daftarOpsi[] = [
                ...$pilihan,
                'persentase' => $persentase,
                'efektif' => $pilihan['is_benar']
                    ? null
                    : ($persentase >= 5.0 && $pilihan['bawah'] >= $pilihan['atas']),
            ];
        }

        return [
            'soal_id' => $soal->id,
            'nomor' => $soal->nomor,
            'tipe' => $soal->tipe->value,
            'tipe_label' => $soal->tipe->getLabel(),
            'pertanyaan' => $soal->pertanyaan,
            'bobot' => (float) $soal->bobot,
            'jumlah_menjawab' => $menjawab,
            'jumlah_benar' => $benar,
            'tingkat_kesukaran' => $tingkatKesukaran,
            'kategori_kesukaran' => $this->kategoriKesukaran($tingkatKesukaran),
            'daya_beda' => $dayaBeda,
            'kategori_daya_beda' => $this->kategoriDayaBeda($dayaBeda),
            'kunci_opsi_id' => $kunci?->id,
            'opsi' => $daftarOpsi,
        ];
    }

    /**
     * @param  Collection<int, PesertaUjian>  $peserta
     * @param  Collection<string, JawabanSiswa>  $jawaban
     * @return array<string, mixed>
     */
    private function analisisSoalObjektif(Soal $soal, Collection $peserta, Collection $jawaban): array
    {
        $jumlahPeserta = $peserta->count();
        $kelompok = max(1, (int) ceil($jumlahPeserta * 0.27));
        $idAtas = $peserta->take($kelompok)->pluck('id')->all();
        $idBawah = $peserta->reverse()->take($kelompok)->pluck('id')->all();

        $daftarOpsi = [];

        foreach ($soal->opsi as $opsi) {
            $daftarOpsi[$opsi->id] = [
                'id' => $opsi->id,
                'label' => $opsi->label,
                'teks' => $opsi->teks,
                'is_benar' => (bool) $opsi->is_benar,
                'dipilih' => 0,
                'atas' => 0,
                'bawah' => 0,
            ];
        }

        $jumlahMenjawab = 0;
        $jumlahBenar = 0;
        $benarAtas = 0;
        $benarBawah = 0;

        foreach ($peserta as $item) {
            $baris = $jawaban->get($item->id.':'.$soal->id);
            $payload = $baris?->jawaban;

            if (blank($payload)) {
                continue;
            }

            $jumlahMenjawab++;

            $isBenar = (bool) ($baris?->is_benar ?? false);
            $jumlahBenar += $isBenar ? 1 : 0;
            $benarAtas += $isBenar && in_array($item->id, $idAtas, true) ? 1 : 0;
            $benarBawah += $isBenar && in_array($item->id, $idBawah, true) ? 1 : 0;

            foreach ($this->opsiDipilih($soal, $payload) as $opsiId) {
                if (! isset($daftarOpsi[$opsiId])) {
                    continue;
                }

                $daftarOpsi[$opsiId]['dipilih']++;

                if (in_array($item->id, $idAtas, true)) {
                    $daftarOpsi[$opsiId]['atas']++;
                }

                if (in_array($item->id, $idBawah, true)) {
                    $daftarOpsi[$opsiId]['bawah']++;
                }
            }
        }

        $tingkatKesukaran = $jumlahPeserta > 0 ? round($jumlahBenar / $jumlahPeserta, 4) : null;
        $dayaBeda = $jumlahPeserta >= 2
            ? round(($benarAtas / $kelompok) - ($benarBawah / $kelompok), 4)
            : null;

        $opsi = [];

        foreach ($daftarOpsi as $pilihan) {
            $persentase = $jumlahPeserta > 0 ? round($pilihan['dipilih'] / $jumlahPeserta * 100, 2) : 0.0;

            $opsi[] = [
                ...$pilihan,
                'persentase' => $persentase,
                'efektif' => $pilihan['is_benar']
                    ? null
                    : ($persentase >= 5.0 && $pilihan['bawah'] >= $pilihan['atas']),
            ];
        }

        return [
            'soal_id' => $soal->id,
            'nomor' => $soal->nomor,
            'tipe' => $soal->tipe->value,
            'tipe_label' => $soal->tipe->getLabel(),
            'pertanyaan' => $soal->pertanyaan,
            'bobot' => (float) $soal->bobot,
            'jumlah_menjawab' => $jumlahMenjawab,
            'jumlah_benar' => $jumlahBenar,
            'tingkat_kesukaran' => $tingkatKesukaran,
            'kategori_kesukaran' => $this->kategoriKesukaran($tingkatKesukaran),
            'daya_beda' => $dayaBeda,
            'kategori_daya_beda' => $this->kategoriDayaBeda($dayaBeda),
            'kunci_opsi_id' => null,
            'opsi' => $opsi,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function opsiDipilih(Soal $soal, mixed $payload): array
    {
        if ($soal->tipe === TipeSoal::IsianSingkat) {
            $teks = $this->normalisasiTeks(data_get($payload, 'teks'));

            if ($teks === '') {
                return [];
            }

            return $soal->opsi
                ->filter(fn (mixed $opsi): bool => (bool) $opsi->is_benar && $this->normalisasiTeks($opsi->teks) === $teks)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();
        }

        $nilai = match ($soal->tipe) {
            TipeSoal::PgKompleks => data_get($payload, 'opsi_id', []),
            TipeSoal::Menjodohkan => array_keys((array) data_get($payload, 'pasangan', [])),
            TipeSoal::BenarSalahPernyataan => array_keys((array) data_get($payload, 'pernyataan', [])),
            TipeSoal::Urutan => data_get($payload, 'urutan', []),
            default => data_get($payload, 'opsi_id'),
        };

        return collect(is_array($nilai) ? $nilai : [$nilai])
            ->filter(fn (mixed $id): bool => $id !== null && $id !== '')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    private function normalisasiTeks(mixed $teks): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $teks) ?? ''), 'UTF-8');
    }

    private function kategoriKesukaran(?float $nilai): string
    {
        if ($nilai === null) {
            return '-';
        }

        return match (true) {
            $nilai >= 0.7 => 'Mudah',
            $nilai >= 0.3 => 'Sedang',
            default => 'Sulit',
        };
    }

    private function kategoriDayaBeda(?float $nilai): string
    {
        if ($nilai === null) {
            return '-';
        }

        return match (true) {
            $nilai >= 0.4 => 'Baik',
            $nilai >= 0.2 => 'Cukup',
            default => 'Jelek',
        };
    }
}
