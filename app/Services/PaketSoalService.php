<?php

namespace App\Services;

use App\Enums\StatusPaketSoal;
use App\Enums\TipeSoal;
use App\Models\OpsiSoal;
use App\Models\PaketSoal;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaketSoalService
{
    /**
     * Validate all questions and mark the package as ready for scheduling.
     */
    public function markReady(PaketSoal $paketSoal): PaketSoal
    {
        $paketSoal->load('soal.opsi');

        if ($paketSoal->soal->isEmpty()) {
            throw ValidationException::withMessages([
                'soal' => 'Paket soal harus memiliki minimal satu soal sebelum ditandai siap.',
            ]);
        }

        $errors = [];

        foreach ($paketSoal->soal as $soal) {
            $errors = [...$errors, ...$this->validasiSoal($soal)];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['soal' => $errors]);
        }

        $paketSoal->update(['status' => StatusPaketSoal::Siap]);

        return $paketSoal->refresh();
    }

    public function duplikat(PaketSoal $sumber, User $pembuat): DuplikatPaketResult
    {
        $fileHilang = 0;

        $salinan = DB::transaction(function () use ($sumber, $pembuat, &$fileHilang): PaketSoal {
            $sumber->loadMissing('soal.opsi');

            /** @var PaketSoal $salinan */
            $salinan = $sumber->replicate();
            $salinan->kode = $this->kodeUnik($sumber->kode);
            $salinan->nama = Str::limit($sumber->nama.' (Salinan)', 150, '');
            $salinan->status = StatusPaketSoal::Draft;
            $salinan->dibuat_oleh = $pembuat->getKey();
            $salinan->save();

            foreach ($sumber->soal as $soal) {
                /** @var Soal $soal */
                $soalSalinan = $soal->replicate();
                $soalSalinan->paket_soal_id = $salinan->getKey();
                $soalSalinan->gambar = $this->salinFile($soal->gambar, 'soal', $fileHilang);
                $soalSalinan->audio = $this->salinFile($soal->audio, 'soal/audio', $fileHilang);
                $soalSalinan->save();

                foreach ($soal->opsi as $opsi) {
                    /** @var OpsiSoal $opsi */
                    $opsiSalinan = $opsi->replicate();
                    $opsiSalinan->soal_id = $soalSalinan->getKey();
                    $opsiSalinan->gambar = $this->salinFile($opsi->gambar, 'opsi-soal', $fileHilang);
                    $opsiSalinan->save();
                }
            }

            return $salinan;
        });

        return new DuplikatPaketResult($salinan->refresh(), $fileHilang);
    }

    /**
     * @return array<int, string>
     */
    private function validasiSoal(Soal $soal): array
    {
        $errors = [];
        $jumlahOpsi = $soal->opsi->count();
        $jumlahBenar = $soal->opsi->where('is_benar', true)->count();

        if ($soal->tipe === TipeSoal::Essay) {
            return ["Soal essay nomor {$soal->nomor} belum dapat dipakai karena penilaian manual belum tersedia."];
        }

        if (! $soal->tipe->usesOptions()) {
            return $errors;
        }

        if ($soal->tipe === TipeSoal::IsianSingkat) {
            if ($jumlahOpsi < 1) {
                $errors[] = "Soal isian singkat nomor {$soal->nomor} harus memiliki minimal satu jawaban diterima.";
            }

            if ($jumlahBenar < 1) {
                $errors[] = "Soal isian singkat nomor {$soal->nomor} harus memiliki minimal satu kunci jawaban.";
            }

            return $errors;
        }

        if ($jumlahOpsi < 2) {
            $errors[] = "Soal nomor {$soal->nomor} harus memiliki minimal dua opsi.";

            return $errors;
        }

        if ($soal->tipe === TipeSoal::Pg && $jumlahBenar !== 1) {
            $errors[] = "Soal pilihan ganda nomor {$soal->nomor} harus memiliki tepat satu opsi benar.";
        }

        if ($soal->tipe === TipeSoal::PgKompleks && $jumlahBenar < 2) {
            $errors[] = "Soal pilihan ganda kompleks nomor {$soal->nomor} harus memiliki minimal dua opsi benar.";
        }

        if ($soal->tipe === TipeSoal::BenarSalah && ($jumlahOpsi !== 2 || $jumlahBenar !== 1)) {
            $errors[] = "Soal benar/salah nomor {$soal->nomor} harus memiliki dua opsi dan tepat satu kunci.";
        }

        if ($soal->tipe === TipeSoal::Menjodohkan) {
            $pasangan = $soal->opsi->pluck('pasangan')->map(fn ($value): string => trim((string) $value));

            if ($pasangan->contains('')) {
                $errors[] = "Soal menjodohkan nomor {$soal->nomor} harus memiliki pasangan untuk setiap opsi.";
            }

            if ($pasangan->filter()->unique()->count() !== $pasangan->filter()->count()) {
                $errors[] = "Pasangan pada soal menjodohkan nomor {$soal->nomor} tidak boleh duplikat.";
            }
        }

        if ($soal->tipe === TipeSoal::Urutan) {
            $urutan = $soal->opsi->pluck('urutan');

            if ($urutan->unique()->count() !== $urutan->count()) {
                $errors[] = "Urutan opsi pada soal nomor {$soal->nomor} harus unik.";
            }
        }

        return $errors;
    }

    public function markDraft(PaketSoal $paketSoal): PaketSoal
    {
        $paketSoal->update(['status' => StatusPaketSoal::Draft]);

        return $paketSoal->refresh();
    }

    private function kodeUnik(string $kode): string
    {
        $dasar = Str::limit(Str::upper($kode), 42, '');
        $kandidat = $dasar.'-COPY';
        $urut = 2;

        while (PaketSoal::withTrashed()->where('kode', $kandidat)->exists()) {
            $akhiran = '-COPY-'.$urut;
            $kandidat = Str::limit($dasar, 50 - strlen($akhiran), '').$akhiran;
            $urut++;
        }

        return $kandidat;
    }

    private function salinFile(?string $path, string $directory, int &$fileHilang): ?string
    {
        if (blank($path)) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            $fileHilang++;

            return null;
        }

        $ekstensi = pathinfo($path, PATHINFO_EXTENSION);
        $tujuan = trim($directory, '/').'/'.Str::uuid().($ekstensi !== '' ? '.'.$ekstensi : '');

        try {
            if (! $disk->copy($path, $tujuan)) {
                $fileHilang++;

                return null;
            }
        } catch (Throwable) {
            $fileHilang++;

            return null;
        }

        return $tujuan;
    }
}
