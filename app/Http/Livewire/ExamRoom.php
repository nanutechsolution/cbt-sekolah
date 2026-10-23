<?php

namespace App\Http\Livewire;

use App\Enums\JenisPelanggaran;
use App\Enums\StatusPesertaUjian;
use App\Enums\TipeSoal;
use App\Models\PesertaUjian;
use App\Models\Siswa;
use App\Models\Soal;
use App\Services\PesertaUjianService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ExamRoom extends Component
{
    public PesertaUjian $peserta;

    /** @var array<int, array<string, mixed>> */
    public array $soalList = [];

    /** @var array<int, mixed> */
    public array $jawabanState = [];

    /** @var array<int, bool> */
    public array $raguState = [];

    public int $nomorAktif = 1;

    public int $sisaDetik = 0;

    public int $serverNow = 0;

    public string $saveStatus = '';

    public ?string $errorMessage = null;

    public bool $suspended = false;

    public bool $submitting = false;

    /** @var array<int, int> */
    public array $audioPlayCounts = [];

    /** @var array<int, bool> */
    public array $urutanTersentuh = [];

    public bool $expired = false;

    public function mount(PesertaUjian $peserta): void
    {
        $this->peserta = $peserta->load('jadwalUjian.paketSoal');

        $this->siswa();
        $this->token();

        if ($this->peserta->status === StatusPesertaUjian::Selesai) {
            $this->redirect(route('siswa.ujian.hasil', $this->peserta));

            return;
        }

        if ($this->peserta->status === StatusPesertaUjian::Ditangguhkan) {
            $this->suspended = true;

            return;
        }

        if ($this->peserta->status !== StatusPesertaUjian::Mengerjakan) {
            $this->redirect(route('siswa.ujian.konfirmasi', $this->peserta->jadwal_ujian_id));

            return;
        }

        if ($this->peserta->waktuHabis()) {
            $this->submitExam();

            return;
        }

        $this->muatSoal();
    }

    /**
     * @param  mixed  $value
     */
    public function updatedJawabanState($value, string $key): void
    {
        $soalId = (int) explode('.', (string) $key)[0];

        if ($this->suspended || $this->expired || $soalId <= 0) {
            return;
        }

        $this->simpanSoal($soalId);
    }

    public function toggleRagu(int $soalId): void
    {
        if ($this->suspended || $this->expired) {
            return;
        }

        $baru = ! ($this->raguState[$soalId] ?? false);
        $this->raguState[$soalId] = $baru;

        try {
            app(PesertaUjianService::class)->ubahRagu(
                $this->peserta,
                $this->siswa(),
                $this->token(),
                $soalId,
                $baru,
            );
            $this->saveStatus = 'Tersimpan';
        } catch (ValidationException $exception) {
            $this->raguState[$soalId] = ! $baru;
            $this->tanganiValidation($exception);
        }
    }

    public function pindahSoal(int $nomor): void
    {
        $jumlah = count($this->soalList);

        if ($jumlah === 0) {
            return;
        }

        $this->nomorAktif = max(1, min($jumlah, $nomor));
    }

    public function soalBerikutnya(): void
    {
        $this->pindahSoal($this->nomorAktif + 1);
    }

    public function soalSebelumnya(): void
    {
        $this->pindahSoal($this->nomorAktif - 1);
    }

    public function pindahUrutan(int $soalId, int $indeks, int $arah): void
    {
        if ($this->suspended || $this->expired) {
            return;
        }

        $state = $this->jawabanState[$soalId] ?? [];

        if (! is_array($state)) {
            return;
        }

        $urutan = array_values($state);

        if (! isset($urutan[$indeks])) {
            return;
        }

        $tujuan = $indeks + $arah;

        if ($tujuan < 0 || $tujuan >= count($urutan)) {
            return;
        }

        [$urutan[$indeks], $urutan[$tujuan]] = [$urutan[$tujuan], $urutan[$indeks]];
        $this->jawabanState[$soalId] = $urutan;
        $this->urutanTersentuh[$soalId] = true;
        $this->simpanSoal($soalId);
    }

    public function catatPutarAudio(int $soalId): void
    {
        if ($this->suspended || $this->expired) {
            return;
        }

        try {
            $jumlah = app(PesertaUjianService::class)->catatPutarAudio(
                $this->peserta,
                $this->siswa(),
                $this->token(),
                $soalId,
            );
            $this->audioPlayCounts[$soalId] = $jumlah;
        } catch (ValidationException $exception) {
            $this->tanganiValidation($exception);
        }
    }

    public function syncTimer(): void
    {
        $this->peserta = $this->peserta->fresh(['jadwalUjian.paketSoal']);

        if ($this->peserta->status === StatusPesertaUjian::Selesai) {
            $this->redirect(route('siswa.ujian.hasil', $this->peserta));

            return;
        }

        if ($this->peserta->status === StatusPesertaUjian::Ditangguhkan) {
            $this->suspended = true;

            return;
        }

        $this->sisaDetik = $this->peserta->sisaDetik();
        $this->serverNow = now()->timestamp;

        $this->dispatch('timer-sync', seconds: $this->sisaDetik);

        if ($this->sisaDetik <= 0) {
            $this->expired = true;
            $this->dispatch('exam-expired');
        }
    }

    public function submitExam(): void
    {
        if ($this->submitting) {
            return;
        }

        $this->submitting = true;
        $this->errorMessage = null;

        try {
            app(PesertaUjianService::class)->kirim(
                $this->peserta,
                $this->siswa(),
                $this->token(),
            );
        } catch (ValidationException $exception) {
            $this->submitting = false;
            $this->tanganiValidation($exception);

            return;
        }

        $this->redirect(route('siswa.ujian.hasil', $this->peserta->id));
    }

    public function autoSubmit(): void
    {
        $this->submitExam();
    }

    public function recordViolation(string $jenis): void
    {
        if ($this->suspended || $this->submitting) {
            return;
        }

        $jenisPelanggaran = JenisPelanggaran::tryFrom($jenis);

        if ($jenisPelanggaran === null) {
            return;
        }

        try {
            app(PesertaUjianService::class)->catatPelanggaran(
                $this->peserta,
                $this->siswa(),
                $this->token(),
                $jenisPelanggaran,
            );
        } catch (ValidationException $exception) {
            $this->tanganiValidation($exception);

            return;
        }

        $this->peserta->refresh();

        if ($this->peserta->status === StatusPesertaUjian::Ditangguhkan) {
            $this->suspended = true;
        }
    }

    public function render()
    {
        if ($this->suspended || $this->peserta->status === StatusPesertaUjian::Ditangguhkan) {
            return view('livewire.exam-suspended');
        }

        return view('livewire.exam-room');
    }

    private function muatSoal(): void
    {
        $ids = collect($this->peserta->urutan_soal ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->values();

        $soal = Soal::query()
            ->with('opsi')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $jawaban = $this->peserta->jawaban()->get()->keyBy('soal_id');

        foreach ($ids as $id) {
            $butir = $soal->get($id);

            if ($butir === null) {
                continue;
            }

            $urutanOpsi = $this->peserta->urutan_opsi[$id] ?? $butir->opsi->pluck('id')->all();
            $opsiById = $butir->opsi->keyBy('id');

            // Isian singkat menyimpan jawaban yang diterima di opsi_soal.teks.
            // Jangan pernah menaruhnya di properti public agar tidak ikut snapshot Livewire.
            $opsi = [];

            if ($butir->tipe !== TipeSoal::IsianSingkat && $butir->tipe !== TipeSoal::Essay) {
                $opsi = collect($urutanOpsi)
                    ->map(function (mixed $opsiId) use ($opsiById): ?array {
                        $butirOpsi = $opsiById->get((int) $opsiId);

                        if ($butirOpsi === null) {
                            return null;
                        }

                        return [
                            'id' => $butirOpsi->id,
                            'label' => $butirOpsi->label,
                            'teks' => $butirOpsi->teks,
                            'gambar' => $butirOpsi->gambar
                                ? Storage::disk('public')->url($butirOpsi->gambar)
                                : null,
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();
            }

            $this->soalList[] = [
                'id' => $butir->id,
                'nomor' => count($this->soalList) + 1,
                'tipe' => $butir->tipe->value,
                'pertanyaan' => $butir->pertanyaan,
                'gambar' => $butir->gambar
                    ? Storage::disk('public')->url($butir->gambar)
                    : null,
                'audio' => $butir->audio
                    ? Storage::disk('public')->url($butir->audio)
                    : null,
                'audio_maks_putar' => $butir->audio_maks_putar,
                'pilihan_pasangan' => $butir->tipe === TipeSoal::Menjodohkan
                    ? $butir->opsi->pluck('pasangan')
                        ->filter(fn (mixed $value): bool => filled($value))
                        ->map(fn (mixed $value): string => trim((string) $value))
                        ->unique()
                        ->values()
                        ->all()
                    : [],
                'opsi' => $opsi,
            ];

            $baris = $jawaban->get($id);
            $this->jawabanState[$id] = match ($butir->tipe) {
                TipeSoal::PgKompleks => array_values((array) data_get($baris?->jawaban, 'opsi_id', [])),
                TipeSoal::Menjodohkan => (array) data_get($baris?->jawaban, 'pasangan', []),
                TipeSoal::BenarSalahPernyataan => collect((array) data_get($baris?->jawaban, 'pernyataan', []))
                    ->map(fn (mixed $nilai): string => (bool) $nilai ? '1' : '0')
                    ->all(),
                TipeSoal::Urutan => array_values((array) (data_get($baris?->jawaban, 'urutan') ?: $urutanOpsi)),
                TipeSoal::IsianSingkat, TipeSoal::Essay => (string) data_get($baris?->jawaban, 'teks', ''),
                default => data_get($baris?->jawaban, 'opsi_id'),
            };
            $this->urutanTersentuh[$id] = $butir->tipe === TipeSoal::Urutan
                && filled(data_get($baris?->jawaban, 'urutan'));
            $this->audioPlayCounts[$id] = (int) ($baris?->jumlah_putar_audio ?? 0);
            $this->raguState[$id] = (bool) ($baris?->ragu ?? false);
        }

        $this->sisaDetik = $this->peserta->sisaDetik();
        $this->serverNow = now()->timestamp;

        foreach ($this->soalList as $indeks => $butir) {
            $terisi = $butir['tipe'] === TipeSoal::Urutan->value
                ? (bool) ($this->urutanTersentuh[$butir['id']] ?? false)
                : ! blank($this->jawabanState[$butir['id']] ?? null);

            if (! $terisi) {
                $this->nomorAktif = $indeks + 1;

                break;
            }
        }
    }

    private function simpanSoal(int $soalId): void
    {
        $this->saveStatus = 'Menyimpan...';
        $this->errorMessage = null;

        try {
            app(PesertaUjianService::class)->simpanJawaban(
                $this->peserta,
                $this->siswa(),
                $this->token(),
                $soalId,
                $this->jawabanState[$soalId] ?? null,
                (bool) ($this->raguState[$soalId] ?? false),
            );
            $this->saveStatus = 'Tersimpan';
        } catch (ValidationException $exception) {
            $this->saveStatus = '';
            $this->tanganiValidation($exception);
        }
    }

    private function tanganiValidation(ValidationException $exception): void
    {
        $pesan = collect($exception->errors())->flatten()->implode(' ');
        $this->errorMessage = $pesan;

        if (str_contains(mb_strtolower($pesan), 'waktu ujian sudah habis')) {
            $this->expired = true;
            $this->dispatch('exam-expired');
        }
    }

    private function siswa(): Siswa
    {
        /** @var Siswa|null $siswa */
        $siswa = Auth::guard('siswa')->user();

        abort_if($siswa === null || $siswa->id !== $this->peserta->siswa_id, 403);

        return $siswa;
    }

    private function token(): string
    {
        $token = session(PesertaUjianService::kunciSesi($this->peserta->id));

        abort_unless(
            is_string($token) && hash_equals((string) $this->peserta->device_token, $token),
            403,
        );

        return $token;
    }
}
