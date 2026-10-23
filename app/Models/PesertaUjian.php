<?php

namespace App\Models;

use App\Enums\StatusPesertaUjian;
use App\Services\PaketSoalLockService;
use Database\Factories\PesertaUjianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('peserta_ujian')]
#[Fillable([
    'jadwal_ujian_id',
    'siswa_id',
    'status',
    'waktu_mulai',
    'waktu_batas',
    'waktu_selesai',
    'device_token',
    'ip_address',
    'user_agent',
    'urutan_soal',
    'urutan_opsi',
    'jumlah_pelanggaran',
    'nilai',
    'nilai_pg',
    'nilai_essay',
    'terakhir_aktif',
])]
class PesertaUjian extends Model
{
    /** @use HasFactory<PesertaUjianFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (PesertaUjian $peserta): void {
            $peserta->lupakanCachePaket();
        });

        static::deleted(function (PesertaUjian $peserta): void {
            $peserta->lupakanCachePaket();
        });
    }

    private function lupakanCachePaket(): void
    {
        $paketId = JadwalUjian::query()
            ->whereKey($this->jadwal_ujian_id)
            ->value('paket_soal_id');

        if ($paketId !== null) {
            app(PaketSoalLockService::class)->lupakan((int) $paketId);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusPesertaUjian::class,
            'waktu_mulai' => 'datetime',
            'waktu_batas' => 'datetime',
            'waktu_selesai' => 'datetime',
            'terakhir_aktif' => 'datetime',
            'urutan_soal' => 'array',
            'urutan_opsi' => 'array',
            'jumlah_pelanggaran' => 'integer',
            'nilai' => 'decimal:2',
            'nilai_pg' => 'decimal:2',
            'nilai_essay' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<JadwalUjian, $this>
     */
    public function jadwalUjian(): BelongsTo
    {
        return $this->belongsTo(JadwalUjian::class);
    }

    /**
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * @return HasMany<JawabanSiswa, $this>
     */
    public function jawaban(): HasMany
    {
        return $this->hasMany(JawabanSiswa::class);
    }

    /**
     * @return HasMany<PelanggaranUjian, $this>
     */
    public function pelanggaran(): HasMany
    {
        return $this->hasMany(PelanggaranUjian::class);
    }

    public function sisaDetik(): int
    {
        if (! $this->waktu_batas) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->waktu_batas, false));
    }

    public function waktuHabis(): bool
    {
        return $this->waktu_batas !== null && now()->greaterThan($this->waktu_batas);
    }

    public function sedangMengerjakan(): bool
    {
        return $this->status === StatusPesertaUjian::Mengerjakan;
    }
}
