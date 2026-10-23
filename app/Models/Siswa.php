<?php

namespace App\Models;

use Database\Factories\SiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

#[Table('siswa')]
#[Fillable([
    'nis',
    'nisn',
    'nama',
    'jenis_kelamin',
    'tempat_lahir',
    'tanggal_lahir',
    'agama_id',
    'kelas_id',
    'foto',
    'username',
    'security_key',
    'is_aktif',
])]
#[Hidden(['security_key', 'remember_token'])]
class Siswa extends Authenticatable
{
    /** @use HasFactory<SiswaFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'security_key' => 'encrypted',
            'is_aktif' => 'boolean',
        ];
    }

    protected function username(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::lower(trim($value)),
        );
    }

    /**
     * @return BelongsTo<Agama, $this>
     */
    public function agama(): BelongsTo
    {
        return $this->belongsTo(Agama::class);
    }

    /**
     * @return BelongsTo<Kelas, $this>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * @return HasMany<PesertaUjian, $this>
     */
    public function pesertaUjian(): HasMany
    {
        return $this->hasMany(PesertaUjian::class);
    }

    /**
     * @return HasMany<PenempatanPeserta, $this>
     */
    public function penempatanPeserta(): HasMany
    {
        return $this->hasMany(PenempatanPeserta::class);
    }
}
