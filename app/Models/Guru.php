<?php

namespace App\Models;

use Database\Factories\GuruFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('guru')]
#[Fillable(['user_id', 'nip', 'nama', 'jenis_kelamin', 'no_hp'])]
class Guru extends Model
{
    /** @use HasFactory<GuruFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (Guru $guru): void {
            if ($guru->isForceDeleting()) {
                return;
            }

            $guru->user?->update(['is_aktif' => false]);
        });

        static::restoring(function (Guru $guru): void {
            $guru->user?->update(['is_aktif' => true]);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<GuruMengajar, $this>
     */
    public function mengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class);
    }

    /**
     * @return HasMany<Kelas, $this>
     */
    public function kelasWali(): HasMany
    {
        return $this->hasMany(Kelas::class, 'wali_kelas_id');
    }

    /**
     * @return BelongsToMany<Kelas, $this>
     */
    public function kelasDiajar(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'guru_mengajar')
            ->withPivot(['mapel_id', 'tahun_ajaran_id'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Mapel, $this>
     */
    public function mapelDiajar(): BelongsToMany
    {
        return $this->belongsToMany(Mapel::class, 'guru_mengajar')
            ->withPivot(['kelas_id', 'tahun_ajaran_id'])
            ->withTimestamps();
    }
}
