<?php

namespace App\Models;

use Database\Factories\MapelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Table('mapel')]
#[Fillable(['kode', 'nama', 'kelompok', 'is_peminatan'])]
class Mapel extends Model
{
    /** @use HasFactory<MapelFactory> */
    use HasFactory, SoftDeletes;

    protected function kode(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::upper(trim($value)),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_peminatan' => 'boolean',
        ];
    }

    /**
     * @return HasMany<GuruMengajar, $this>
     */
    public function guruMengajar(): HasMany
    {
        return $this->hasMany(GuruMengajar::class);
    }

    /**
     * @return BelongsToMany<Kelas, $this>
     */
    public function kelasPeminatan(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'mapel_peminatan_kelas')
            ->withTimestamps();
    }
}
