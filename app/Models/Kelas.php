<?php

namespace App\Models;

use Database\Factories\KelasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('kelas')]
#[Fillable(['nama', 'tingkat', 'jurusan_id', 'wali_kelas_id'])]
class Kelas extends Model
{
    /** @use HasFactory<KelasFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tingkat' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Jurusan, $this>
     */
    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    /**
     * @return BelongsTo<Guru, $this>
     */
    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'wali_kelas_id');
    }

    /**
     * @return HasMany<Siswa, $this>
     */
    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }

    /**
     * @return BelongsToMany<Mapel, $this>
     */
    public function mapelPeminatan(): BelongsToMany
    {
        return $this->belongsToMany(Mapel::class, 'mapel_peminatan_kelas')
            ->withTimestamps();
    }

    /**
     * @return HasMany<GuruMengajar, $this>
     */
    public function guruMengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class);
    }

    /**
     * @return BelongsToMany<JadwalUjian, $this>
     */
    public function jadwalUjian(): BelongsToMany
    {
        return $this->belongsToMany(JadwalUjian::class, 'jadwal_ujian_kelas')
            ->withTimestamps();
    }
}
