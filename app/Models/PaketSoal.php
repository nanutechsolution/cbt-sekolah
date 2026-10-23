<?php

namespace App\Models;

use App\Enums\StatusPaketSoal;
use App\Enums\UserRole;
use App\Services\PaketSoalLockService;
use Database\Factories\PaketSoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Table('paket_soal')]
#[Fillable([
    'kode',
    'nama',
    'mapel_id',
    'jenis_ujian_id',
    'tahun_ajaran_id',
    'tingkat',
    'dibuat_oleh',
    'acak_soal',
    'acak_opsi',
    'tampilkan_nilai',
    'status',
])]
class PaketSoal extends Model
{
    /** @use HasFactory<PaketSoalFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::updating(function (PaketSoal $paket): void {
            app(PaketSoalLockService::class)->pastikanPerubahanPaketDiizinkan($paket);
        });

        static::deleting(function (PaketSoal $paket): void {
            app(PaketSoalLockService::class)->pastikanTidakTerkunci($paket);
        });

        static::saved(function (PaketSoal $paket): void {
            app(PaketSoalLockService::class)->lupakan($paket);
        });

        static::deleted(function (PaketSoal $paket): void {
            app(PaketSoalLockService::class)->lupakan($paket);
        });
    }

    public function isTerkunci(): bool
    {
        return app(PaketSoalLockService::class)->isTerkunci($this);
    }

    protected function kode(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::upper(trim($value)),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tingkat' => 'integer',
            'acak_soal' => 'boolean',
            'acak_opsi' => 'boolean',
            'tampilkan_nilai' => 'boolean',
            'status' => StatusPaketSoal::class,
        ];
    }

    /**
     * @return BelongsTo<Mapel, $this>
     */
    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class);
    }

    /**
     * @return BelongsTo<JenisUjian, $this>
     */
    public function jenisUjian(): BelongsTo
    {
        return $this->belongsTo(JenisUjian::class);
    }

    /**
     * @return BelongsTo<TahunAjaran, $this>
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * @return HasMany<Soal, $this>
     */
    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class)->orderBy('nomor');
    }

    /**
     * @return HasMany<JadwalUjian, $this>
     */
    public function jadwalUjian(): HasMany
    {
        return $this->hasMany(JadwalUjian::class);
    }

    /**
     * Batasi paket soal yang boleh diakses seorang pengguna.
     */
    public function scopeTampilUntukUser(Builder $query, User $user): Builder
    {
        if ($user->role === UserRole::Admin) {
            return $query;
        }

        $guruId = $user->guru?->id;

        if ($guruId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($user, $guruId): void {
            $query->where('dibuat_oleh', $user->id)
                ->orWhereExists(function ($query) use ($guruId): void {
                    $query->selectRaw('1')
                        ->from('guru_mengajar')
                        ->join('kelas', 'kelas.id', '=', 'guru_mengajar.kelas_id')
                        ->whereColumn('guru_mengajar.mapel_id', 'paket_soal.mapel_id')
                        ->whereColumn('guru_mengajar.tahun_ajaran_id', 'paket_soal.tahun_ajaran_id')
                        ->whereColumn('kelas.tingkat', 'paket_soal.tingkat')
                        ->where('guru_mengajar.guru_id', $guruId);
                });
        });
    }
}
