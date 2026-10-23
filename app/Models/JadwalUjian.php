<?php

namespace App\Models;

use App\Enums\StatusJadwalUjian;
use App\Services\PaketSoalLockService;
use Carbon\CarbonInterface;
use Database\Factories\JadwalUjianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('jadwal_ujian')]
#[Fillable([
    'paket_soal_id',
    'nama',
    'mulai',
    'selesai',
    'durasi_menit',
    'status',
    'dibuat_oleh',
])]
class JadwalUjian extends Model
{
    /** @use HasFactory<JadwalUjianFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (JadwalUjian $jadwal): void {
            app(PaketSoalLockService::class)->lupakan((int) $jadwal->paket_soal_id);
        });

        static::deleted(function (JadwalUjian $jadwal): void {
            app(PaketSoalLockService::class)->lupakan((int) $jadwal->paket_soal_id);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mulai' => 'datetime',
            'selesai' => 'datetime',
            'durasi_menit' => 'integer',
            'status' => StatusJadwalUjian::class,
        ];
    }

    /**
     * @return BelongsTo<PaketSoal, $this>
     */
    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * @return BelongsToMany<Kelas, $this>
     */
    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'jadwal_ujian_kelas')
            ->withTimestamps();
    }

    /**
     * @return HasMany<PesertaUjian, $this>
     */
    public function pesertaUjian(): HasMany
    {
        return $this->hasMany(PesertaUjian::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', StatusJadwalUjian::Aktif->value);
    }

    public function dalamJendela(?CarbonInterface $waktu = null): bool
    {
        $waktu ??= now();

        return $waktu->greaterThanOrEqualTo($this->mulai)
            && $waktu->lessThanOrEqualTo($this->selesai);
    }

    public function bisaDimulai(?CarbonInterface $waktu = null): bool
    {
        return $this->status === StatusJadwalUjian::Aktif
            && $this->dalamJendela($waktu);
    }
}
