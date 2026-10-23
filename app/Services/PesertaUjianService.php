<?php

namespace App\Services;

use App\Enums\JenisAksiLog;
use App\Enums\JenisPelanggaran;
use App\Enums\StatusJadwalUjian;
use App\Enums\StatusPesertaUjian;
use App\Enums\TipeSoal;
use App\Models\JadwalUjian;
use App\Models\JawabanSiswa;
use App\Models\OpsiSoal;
use App\Models\PengaturanSekolah;
use App\Models\PesertaUjian;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PesertaUjianService
{
    public const MAKS_TAMBAH_WAKTU_MENIT = 120;

    public function __construct(
        private readonly PenilaianService $penilaianService,
        private readonly LogAktivitasService $logAktivitasService,
    ) {}

    public static function kunciSesi(int $pesertaId): string
    {
        return "exam_device_token_{$pesertaId}";
    }

    /**
     * Start, or resume, an exam for a student.
     */
    public function mulai(
        JadwalUjian $jadwal,
        Siswa $siswa,
        ?string $tokenSesi = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): PesertaUjian {
        return DB::transaction(function () use ($jadwal, $siswa, $tokenSesi, $ipAddress, $userAgent): PesertaUjian {
            $jadwal = JadwalUjian::query()
                ->with('paketSoal')
                ->lockForUpdate()
                ->findOrFail($jadwal->id);

            $this->pastikanJadwalBisaDimulai($jadwal, $siswa);

            $peserta = PesertaUjian::query()
                ->where('jadwal_ujian_id', $jadwal->id)
                ->where('siswa_id', $siswa->id)
                ->lockForUpdate()
                ->first();

            if ($peserta?->status === StatusPesertaUjian::Selesai) {
                throw ValidationException::withMessages([
                    'ujian' => 'Ujian ini sudah Anda selesaikan.',
                ]);
            }

            if ($peserta?->status === StatusPesertaUjian::Ditangguhkan) {
                throw ValidationException::withMessages([
                    'ujian' => 'Ujian Anda ditangguhkan. Hubungi pengawas.',
                ]);
            }

            if ($peserta?->status === StatusPesertaUjian::Mengerjakan) {
                if ($tokenSesi !== null && hash_equals((string) $peserta->device_token, $tokenSesi)) {
                    return $peserta;
                }

                // Admin can unlock a participant without deleting their answers.
                // A cleared token is the only state that allows a new device to take over.
                if (blank($peserta->device_token)) {
                    if ($peserta->waktu_batas === null || $peserta->sisaDetik() <= 0) {
                        throw ValidationException::withMessages([
                            'ujian' => 'Waktu ujian sudah habis.',
                        ]);
                    }

                    $peserta->update([
                        'device_token' => Str::random(80),
                        'ip_address' => $ipAddress,
                        'user_agent' => $userAgent,
                        'terakhir_aktif' => now(),
                    ]);

                    return $peserta->refresh();
                }

                throw ValidationException::withMessages([
                    'ujian' => 'Ujian sudah dimulai pada perangkat lain. Hubungi pengawas untuk membuka kunci device.',
                ]);
            }

            [$urutanSoal, $urutanOpsi] = $this->buatUrutan($jadwal);

            $waktuMulai = now();
            $waktuBatas = $waktuMulai->copy()->addMinutes($jadwal->durasi_menit);

            if ($waktuBatas->greaterThan($jadwal->selesai)) {
                $waktuBatas = $jadwal->selesai->copy();
            }

            if ($waktuBatas->lessThanOrEqualTo($waktuMulai)) {
                throw ValidationException::withMessages([
                    'ujian' => 'Waktu ujian sudah berakhir.',
                ]);
            }

            $token = Str::random(80);

            $atribut = [
                'status' => StatusPesertaUjian::Mengerjakan,
                'waktu_mulai' => $waktuMulai,
                'waktu_batas' => $waktuBatas,
                'device_token' => $token,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'urutan_soal' => $urutanSoal,
                'urutan_opsi' => $urutanOpsi,
                'terakhir_aktif' => $waktuMulai,
            ];

            if ($peserta === null) {
                $peserta = PesertaUjian::query()->create([
                    'jadwal_ujian_id' => $jadwal->id,
                    'siswa_id' => $siswa->id,
                    ...$atribut,
                ]);
            } else {
                $peserta->update($atribut);
            }

            return $peserta->refresh();
        });
    }

    public function pastikanAkses(PesertaUjian $peserta, Siswa $siswa, string $token): void
    {
        if ($peserta->siswa_id !== $siswa->id) {
            throw new AuthorizationException('Peserta ujian bukan milik siswa ini.');
        }

        if ($peserta->status !== StatusPesertaUjian::Mengerjakan) {
            throw ValidationException::withMessages([
                'ujian' => 'Sesi ujian tidak sedang berlangsung.',
            ]);
        }

        if (blank($peserta->device_token) || ! hash_equals((string) $peserta->device_token, $token)) {
            throw new AuthorizationException('Perangkat tidak sesuai dengan sesi ujian.');
        }

        if ($peserta->waktuHabis()) {
            throw ValidationException::withMessages([
                'ujian' => 'Waktu ujian sudah habis.',
            ]);
        }
    }

    /**
     * Save an answer through an upsert and validate its payload on the server.
     */
    public function simpanJawaban(
        PesertaUjian $peserta,
        Siswa $siswa,
        string $token,
        int $soalId,
        mixed $jawaban,
        bool $ragu = false,
    ): JawabanSiswa {
        $this->pastikanAkses($peserta, $siswa, $token);

        $soal = Soal::query()
            ->with('opsi')
            ->where('paket_soal_id', $peserta->jadwalUjian->paket_soal_id)
            ->findOrFail($soalId);

        if (! in_array($soal->id, $peserta->urutan_soal ?? [], true)) {
            throw ValidationException::withMessages([
                'jawaban' => 'Soal tidak termasuk dalam paket ujian ini.',
            ]);
        }

        $payload = $this->validasiJawaban($peserta, $soal, $jawaban);

        $jawabanSiswa = JawabanSiswa::query()->updateOrCreate(
            [
                'peserta_ujian_id' => $peserta->id,
                'soal_id' => $soal->id,
            ],
            [
                'jawaban' => $payload,
                'ragu' => $ragu,
                'dijawab_pada' => now(),
            ],
        );

        $peserta->update(['terakhir_aktif' => now()]);

        return $jawabanSiswa;
    }

    public function ubahRagu(PesertaUjian $peserta, Siswa $siswa, string $token, int $soalId, bool $ragu): JawabanSiswa
    {
        $jawaban = $peserta->jawaban()->where('soal_id', $soalId)->first();

        return $this->simpanJawaban(
            $peserta,
            $siswa,
            $token,
            $soalId,
            $jawaban?->jawaban,
            $ragu,
        );
    }

    public function catatPelanggaran(
        PesertaUjian $peserta,
        Siswa $siswa,
        string $token,
        JenisPelanggaran $jenis,
        ?string $keterangan = null,
    ): int {
        $this->pastikanAkses($peserta, $siswa, $token);

        $peserta->pelanggaran()->create([
            'jenis' => $jenis,
            'keterangan' => $keterangan,
        ]);

        $peserta->increment('jumlah_pelanggaran');
        $peserta->refresh();

        $pengaturan = PengaturanSekolah::query()->first();
        $batas = max(1, (int) ($pengaturan?->batas_pelanggaran ?? 5));

        if ($peserta->jumlah_pelanggaran >= $batas && ($pengaturan?->auto_tangguhkan ?? true)) {
            $peserta->update(['status' => StatusPesertaUjian::Ditangguhkan]);
        }

        return $peserta->jumlah_pelanggaran;
    }

    public function kirim(PesertaUjian $peserta, Siswa $siswa, string $token): PesertaUjian
    {
        if ($peserta->siswa_id !== $siswa->id) {
            throw new AuthorizationException('Peserta ujian bukan milik siswa ini.');
        }

        if (blank($peserta->device_token) || ! hash_equals((string) $peserta->device_token, $token)) {
            throw new AuthorizationException('Perangkat tidak sesuai dengan sesi ujian.');
        }

        if ($peserta->status === StatusPesertaUjian::Selesai) {
            return $peserta;
        }

        if ($peserta->status !== StatusPesertaUjian::Mengerjakan) {
            throw ValidationException::withMessages([
                'ujian' => 'Ujian belum dimulai atau sudah ditangguhkan.',
            ]);
        }

        return $this->penilaianService->nilai($peserta);
    }

    /**
     * Add time without touching answers, order, violations, or device session.
     */
    public function tambahWaktu(PesertaUjian $peserta, int $menit, ?User $oleh = null): PesertaUjian
    {
        if ($menit < 1 || $menit > self::MAKS_TAMBAH_WAKTU_MENIT) {
            throw ValidationException::withMessages([
                'menit' => 'Tambahan waktu harus antara 1 dan '.self::MAKS_TAMBAH_WAKTU_MENIT.' menit.',
            ]);
        }

        return DB::transaction(function () use ($peserta, $menit, $oleh): PesertaUjian {
            $jadwal = JadwalUjian::query()
                ->lockForUpdate()
                ->findOrFail($peserta->jadwal_ujian_id);

            $peserta = PesertaUjian::query()
                ->lockForUpdate()
                ->findOrFail($peserta->id);

            $this->pastikanPesertaSedangMengerjakan($peserta, 'ditambah waktunya');
            $this->pastikanJadwalMasihBerjalan($jadwal);

            if ($peserta->waktu_batas === null || $peserta->sisaDetik() <= 0) {
                throw ValidationException::withMessages([
                    'menit' => 'Waktu peserta sudah habis; tambah waktu tidak dapat dilakukan.',
                ]);
            }

            $batasLama = $peserta->waktu_batas->copy();
            $sisaDetikSebelum = $peserta->sisaDetik();
            $batasBaru = $batasLama->copy()->addMinutes($menit);

            if ($batasBaru->greaterThan($jadwal->selesai)) {
                $maksMenit = max(0, (int) floor(($jadwal->selesai->getTimestamp() - $batasLama->getTimestamp()) / 60));

                throw ValidationException::withMessages([
                    'menit' => "Penambahan waktu melewati jam selesai jadwal. Maksimal {$maksMenit} menit.",
                ]);
            }

            $peserta->update(['waktu_batas' => $batasBaru]);

            $this->logAktivitasService->catat(
                JenisAksiLog::TambahWaktu,
                $peserta,
                $oleh,
                "Menambah {$menit} menit waktu peserta.",
                [
                    'waktu_batas' => $batasLama->format('Y-m-d H:i:s'),
                    'sisa_detik' => $sisaDetikSebelum,
                ],
                [
                    'waktu_batas' => $batasBaru->format('Y-m-d H:i:s'),
                    'tambahan_menit' => $menit,
                ],
            );

            return $peserta->refresh();
        });
    }

    /**
     * Unlock the current device without deleting answers or changing the timer.
     */
    public function bukaKunciDevice(PesertaUjian $peserta, ?User $oleh = null): PesertaUjian
    {
        return DB::transaction(function () use ($peserta, $oleh): PesertaUjian {
            $jadwal = JadwalUjian::query()
                ->lockForUpdate()
                ->findOrFail($peserta->jadwal_ujian_id);

            $peserta = PesertaUjian::query()
                ->lockForUpdate()
                ->findOrFail($peserta->id);

            $this->pastikanPesertaSedangMengerjakan($peserta, 'dibuka kunci device-nya');
            $this->pastikanJadwalMasihBerjalan($jadwal);

            if ($peserta->waktu_batas === null || $peserta->sisaDetik() <= 0) {
                throw ValidationException::withMessages([
                    'device' => 'Waktu peserta sudah habis; device tidak dapat dibuka kuncinya.',
                ]);
            }

            if (blank($peserta->device_token)) {
                throw ValidationException::withMessages([
                    'device' => 'Perangkat peserta sudah tidak terkunci.',
                ]);
            }

            $ipAddressLama = $peserta->ip_address;

            $peserta->update([
                'device_token' => null,
                'ip_address' => null,
                'user_agent' => null,
            ]);

            $this->logAktivitasService->catat(
                JenisAksiLog::BukaKunciDevice,
                $peserta,
                $oleh,
                'Membuka kunci device peserta tanpa menghapus jawaban.',
                [
                    'device_terkunci' => true,
                    'ip_address' => $ipAddressLama,
                ],
                [
                    'device_terkunci' => false,
                    'ip_address' => null,
                ],
            );

            return $peserta->refresh();
        });
    }

    /**
     * End and grade a participant manually, with an audit trail.
     */
    public function selesaikanManual(PesertaUjian $peserta, ?User $oleh = null): PesertaUjian
    {
        return DB::transaction(function () use ($peserta, $oleh): PesertaUjian {
            $peserta = PesertaUjian::query()
                ->lockForUpdate()
                ->findOrFail($peserta->id);

            $this->pastikanPesertaSedangMengerjakan($peserta, 'diselesaikan secara manual');

            $dataSebelum = [
                'status' => $peserta->status->value,
                'nilai' => $peserta->nilai,
                'nilai_pg' => $peserta->nilai_pg,
            ];

            $hasil = $this->penilaianService->nilai($peserta);

            $this->logAktivitasService->catat(
                JenisAksiLog::SelesaikanManual,
                $hasil,
                $oleh,
                'Menyelesaikan dan menilai peserta secara manual.',
                $dataSebelum,
                [
                    'status' => $hasil->status->value,
                    'nilai' => $hasil->nilai,
                    'nilai_pg' => $hasil->nilai_pg,
                ],
            );

            return $hasil;
        });
    }

    /**
     * Reset a participant so they can start again on a fresh device.
     */
    public function reset(PesertaUjian $peserta, ?User $oleh = null): PesertaUjian
    {
        return DB::transaction(function () use ($peserta, $oleh): PesertaUjian {
            $peserta = PesertaUjian::query()->lockForUpdate()->findOrFail($peserta->id);

            $dataSebelum = [
                'status' => $peserta->status->value,
                'jumlah_jawaban' => $peserta->jawaban()->count(),
                'jumlah_pelanggaran' => $peserta->pelanggaran()->count(),
                'device_terkunci' => filled($peserta->device_token),
                'nilai' => $peserta->nilai,
            ];

            $peserta->jawaban()->delete();
            $peserta->pelanggaran()->delete();

            $peserta->update([
                'status' => StatusPesertaUjian::Belum,
                'waktu_mulai' => null,
                'waktu_batas' => null,
                'waktu_selesai' => null,
                'device_token' => null,
                'ip_address' => null,
                'user_agent' => null,
                'urutan_soal' => null,
                'urutan_opsi' => null,
                'jumlah_pelanggaran' => 0,
                'nilai' => null,
                'nilai_pg' => null,
                'nilai_essay' => null,
                'terakhir_aktif' => null,
            ]);

            $this->logAktivitasService->catat(
                JenisAksiLog::ResetPeserta,
                $peserta,
                $oleh,
                'Mereset peserta dan menghapus seluruh jawaban serta pelanggaran.',
                $dataSebelum,
                [
                    'status' => StatusPesertaUjian::Belum->value,
                    'jumlah_jawaban' => 0,
                    'jumlah_pelanggaran' => 0,
                    'device_terkunci' => false,
                    'nilai' => null,
                ],
            );

            return $peserta->refresh();
        });
    }

    /**
     * Finalise participants whose server-side time has expired.
     */
    public function tutupYangKedaluwarsa(): int
    {
        $peserta = PesertaUjian::query()
            ->where('status', StatusPesertaUjian::Mengerjakan->value)
            ->whereNotNull('waktu_batas')
            ->where('waktu_batas', '<=', now())
            ->get();

        foreach ($peserta as $item) {
            $this->penilaianService->nilai($item);
        }

        return $peserta->count();
    }

    /**
     * @return array{0: array<int, int>, 1: array<int, array<int, int>>}
     */
    private function buatUrutan(JadwalUjian $jadwal): array
    {
        $paket = $jadwal->paketSoal;

        $soal = $paket->soal()->with('opsi')->get();

        if ($soal->isEmpty()) {
            throw ValidationException::withMessages([
                'ujian' => 'Paket soal tidak memiliki soal.',
            ]);
        }

        $ids = $soal->pluck('id')->map(fn (int $id): int => $id)->all();

        if ($paket->acak_soal) {
            shuffle($ids);
        }

        $urutanOpsi = [];

        foreach ($soal as $butir) {
            $opsiIds = $butir->opsi->pluck('id')->map(fn (int $id): int => $id)->all();

            if ($paket->acak_opsi) {
                shuffle($opsiIds);
            }

            $urutanOpsi[$butir->id] = $opsiIds;
        }

        return [$ids, $urutanOpsi];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validasiJawaban(PesertaUjian $peserta, Soal $soal, mixed $jawaban): ?array
    {
        return match ($soal->tipe) {
            TipeSoal::Pg, TipeSoal::BenarSalah => $this->validasiSatuOpsi($peserta, $soal, $jawaban),
            TipeSoal::PgKompleks => $this->validasiBanyakOpsi($peserta, $soal, $jawaban),
            TipeSoal::IsianSingkat, TipeSoal::Essay => $this->validasiTeks($soal, $jawaban),
            TipeSoal::Menjodohkan => $this->validasiMenjodohkan($peserta, $soal, $jawaban),
            TipeSoal::BenarSalahPernyataan => $this->validasiPernyataan($peserta, $soal, $jawaban),
            TipeSoal::Urutan => $this->validasiUrutan($peserta, $soal, $jawaban),
        };
    }

    /**
     * @return array{opsi_id: int}|null
     */
    private function validasiSatuOpsi(PesertaUjian $peserta, Soal $soal, mixed $jawaban): ?array
    {
        $opsiId = is_array($jawaban) ? ($jawaban['opsi_id'] ?? null) : $jawaban;

        if ($opsiId === null || $opsiId === '' || ! is_numeric($opsiId)) {
            return null;
        }

        $this->pastikanOpsiValid($peserta, $soal, (int) $opsiId);

        return ['opsi_id' => (int) $opsiId];
    }

    /**
     * @return array{opsi_id: array<int, int>}|null
     */
    private function validasiBanyakOpsi(PesertaUjian $peserta, Soal $soal, mixed $jawaban): ?array
    {
        $opsiIds = is_array($jawaban) ? ($jawaban['opsi_id'] ?? $jawaban) : [$jawaban];

        if (! is_array($opsiIds)) {
            throw ValidationException::withMessages([
                'jawaban' => 'Jawaban pilihan ganda kompleks harus berupa daftar opsi.',
            ]);
        }

        $opsiIds = collect($opsiIds)
            ->filter(fn (mixed $id): bool => $id !== null && $id !== '')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($opsiIds->isEmpty()) {
            return null;
        }

        foreach ($opsiIds as $opsiId) {
            $this->pastikanOpsiValid($peserta, $soal, $opsiId);
        }

        return ['opsi_id' => $opsiIds->all()];
    }

    /**
     * @return array{teks: string}|null
     */
    private function validasiTeks(Soal $soal, mixed $jawaban): ?array
    {
        $teks = is_array($jawaban) ? ($jawaban['teks'] ?? '') : (is_string($jawaban) ? $jawaban : '');

        if (! is_string($teks)) {
            throw ValidationException::withMessages([
                'jawaban' => 'Jawaban harus berupa teks.',
            ]);
        }

        $teks = trim($teks);

        if ($teks === '') {
            return null;
        }

        if (mb_strlen($teks) > 20000) {
            throw ValidationException::withMessages([
                'jawaban' => 'Jawaban terlalu panjang.',
            ]);
        }

        return ['teks' => $teks];
    }

    /**
     * @return array{pasangan: array<int, string>}|null
     */
    private function validasiMenjodohkan(PesertaUjian $peserta, Soal $soal, mixed $jawaban): ?array
    {
        $pasangan = is_array($jawaban) ? ($jawaban['pasangan'] ?? $jawaban) : [];

        if (! is_array($pasangan)) {
            throw ValidationException::withMessages([
                'jawaban' => 'Jawaban menjodohkan tidak valid.',
            ]);
        }

        $pilihanValid = $soal->opsi->pluck('pasangan')
            ->filter(fn (mixed $value): bool => filled($value))
            ->map(fn (mixed $value): string => trim((string) $value))
            ->unique()
            ->values()
            ->all();

        $hasil = [];

        foreach ($pasangan as $opsiId => $nilai) {
            $opsiId = (int) $opsiId;

            $this->pastikanOpsiValid($peserta, $soal, $opsiId);

            $nilai = trim((string) $nilai);

            if ($nilai === '') {
                continue;
            }

            if (! in_array($nilai, $pilihanValid, true)) {
                throw ValidationException::withMessages([
                    'jawaban' => 'Pilihan pasangan tidak valid untuk soal ini.',
                ]);
            }

            $hasil[$opsiId] = $nilai;
        }

        return $hasil === [] ? null : ['pasangan' => $hasil];
    }

    /**
     * @return array{pernyataan: array<int, bool>}|null
     */
    private function validasiPernyataan(PesertaUjian $peserta, Soal $soal, mixed $jawaban): ?array
    {
        $pernyataan = is_array($jawaban) ? ($jawaban['pernyataan'] ?? $jawaban) : [];

        if (! is_array($pernyataan)) {
            throw ValidationException::withMessages([
                'jawaban' => 'Jawaban pernyataan tidak valid.',
            ]);
        }

        $hasil = [];

        foreach ($pernyataan as $opsiId => $nilai) {
            if ($nilai === null || $nilai === '') {
                continue;
            }

            $opsiId = (int) $opsiId;

            $this->pastikanOpsiValid($peserta, $soal, $opsiId);

            if (! in_array($nilai, [true, false, 1, 0, '1', '0'], true)) {
                throw ValidationException::withMessages([
                    'jawaban' => 'Nilai pernyataan harus Benar atau Salah.',
                ]);
            }

            $hasil[$opsiId] = filter_var($nilai, FILTER_VALIDATE_BOOLEAN);
        }

        return $hasil === [] ? null : ['pernyataan' => $hasil];
    }

    /**
     * @return array{urutan: array<int, int>}|null
     */
    private function validasiUrutan(PesertaUjian $peserta, Soal $soal, mixed $jawaban): ?array
    {
        $urutan = is_array($jawaban) ? ($jawaban['urutan'] ?? $jawaban) : [];

        if (! is_array($urutan)) {
            throw ValidationException::withMessages([
                'jawaban' => 'Jawaban urutan tidak valid.',
            ]);
        }

        $ids = collect($urutan)
            ->filter(fn (mixed $id): bool => $id !== null && $id !== '')
            ->map(fn (mixed $id): int => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            return null;
        }

        if ($ids->unique()->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'jawaban' => 'Setiap opsi hanya boleh muncul satu kali pada urutan.',
            ]);
        }

        foreach ($ids as $opsiId) {
            $this->pastikanOpsiValid($peserta, $soal, $opsiId);
        }

        return ['urutan' => $ids->all()];
    }

    private function pastikanOpsiValid(PesertaUjian $peserta, Soal $soal, int $opsiId): void
    {
        $urutanOpsi = $peserta->urutan_opsi[$soal->id] ?? [];

        if (! in_array($opsiId, $urutanOpsi, true)) {
            throw ValidationException::withMessages([
                'jawaban' => 'Opsi jawaban tidak valid untuk soal ini.',
            ]);
        }

        if (! OpsiSoal::query()->whereKey($opsiId)->where('soal_id', $soal->id)->exists()) {
            throw ValidationException::withMessages([
                'jawaban' => 'Opsi jawaban tidak valid untuk soal ini.',
            ]);
        }
    }

    public function catatPutarAudio(
        PesertaUjian $peserta,
        Siswa $siswa,
        string $token,
        int $soalId,
    ): int {
        $this->pastikanAkses($peserta, $siswa, $token);

        $soal = Soal::query()
            ->where('paket_soal_id', $peserta->jadwalUjian->paket_soal_id)
            ->findOrFail($soalId);

        if (! in_array($soal->id, $peserta->urutan_soal ?? [], true)) {
            throw ValidationException::withMessages([
                'audio' => 'Soal ini tidak termasuk dalam paket ujian.',
            ]);
        }

        if (blank($soal->audio)) {
            throw ValidationException::withMessages([
                'audio' => 'Soal ini tidak memiliki audio.',
            ]);
        }

        $jawaban = JawabanSiswa::query()->firstOrCreate(
            [
                'peserta_ujian_id' => $peserta->id,
                'soal_id' => $soal->id,
            ],
            [
                'jawaban' => null,
                'ragu' => false,
                'dijawab_pada' => null,
            ],
        );

        $jumlah = (int) $jawaban->jumlah_putar_audio + 1;

        if ($soal->audio_maks_putar !== null && $jumlah > (int) $soal->audio_maks_putar) {
            throw ValidationException::withMessages([
                'audio' => 'Batas pemutaran audio untuk soal ini sudah tercapai.',
            ]);
        }

        $jawaban->update(['jumlah_putar_audio' => $jumlah]);
        $peserta->update(['terakhir_aktif' => now()]);

        return $jumlah;
    }

    private function pastikanPesertaSedangMengerjakan(PesertaUjian $peserta, string $aksi): void
    {
        if ($peserta->status !== StatusPesertaUjian::Mengerjakan) {
            throw ValidationException::withMessages([
                'peserta' => "Peserta hanya dapat {$aksi} jika sedang mengerjakan.",
            ]);
        }
    }

    private function pastikanJadwalMasihBerjalan(JadwalUjian $jadwal): void
    {
        if ($jadwal->status !== StatusJadwalUjian::Aktif) {
            throw ValidationException::withMessages([
                'peserta' => 'Jadwal ujian tidak sedang aktif.',
            ]);
        }

        $sekarang = now();

        if ($sekarang->lessThan($jadwal->mulai) || $sekarang->greaterThanOrEqualTo($jadwal->selesai)) {
            throw ValidationException::withMessages([
                'peserta' => 'Jadwal ujian sudah tidak berjalan.',
            ]);
        }
    }

    private function pastikanJadwalBisaDimulai(JadwalUjian $jadwal, Siswa $siswa): void
    {
        if ($jadwal->status !== StatusJadwalUjian::Aktif) {
            throw ValidationException::withMessages([
                'ujian' => 'Jadwal ujian belum diaktifkan.',
            ]);
        }

        if (! $jadwal->kelas()->whereKey($siswa->kelas_id)->exists()) {
            throw ValidationException::withMessages([
                'ujian' => 'Anda tidak terdaftar pada kelas untuk ujian ini.',
            ]);
        }

        $sekarang = Carbon::now();

        if ($sekarang->lessThan($jadwal->mulai)) {
            throw ValidationException::withMessages([
                'ujian' => 'Ujian belum dimulai.',
            ]);
        }

        if ($sekarang->greaterThan($jadwal->selesai)) {
            throw ValidationException::withMessages([
                'ujian' => 'Waktu ujian sudah berakhir.',
            ]);
        }
    }
}
