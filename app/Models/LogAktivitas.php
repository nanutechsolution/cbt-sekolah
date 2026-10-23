<?php

namespace App\Models;

use App\Enums\JenisAksiLog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Table('log_aktivitas')]
#[Fillable([
    'pengguna_id',
    'pengguna_nama',
    'aksi',
    'entitas_type',
    'entitas_id',
    'keterangan',
    'data_sebelum',
    'data_sesudah',
    'ip_address',
])]
class LogAktivitas extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aksi' => JenisAksiLog::class,
            'data_sebelum' => 'array',
            'data_sesudah' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function entitas(): MorphTo
    {
        return $this->morphTo();
    }

    public function getEntitasLabelAttribute(): string
    {
        if (blank($this->entitas_type)) {
            return '-';
        }

        $label = match ($this->entitas_type) {
            PesertaUjian::class => 'Peserta Ujian',
            User::class => 'Akun',
            Siswa::class => 'Siswa',
            default => class_basename((string) $this->entitas_type),
        };

        return "{$label} #{$this->entitas_id}";
    }
}
