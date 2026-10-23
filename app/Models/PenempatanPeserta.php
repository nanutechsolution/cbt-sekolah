<?php

namespace App\Models;

use Database\Factories\PenempatanPesertaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('penempatan_peserta')]
#[Fillable([
    'siswa_id',
    'tahun_ajaran_id',
    'jenis_ujian_id',
    'ruang_id',
    'sesi_id',
    'no_peserta',
])]
class PenempatanPeserta extends Model
{
    /** @use HasFactory<PenempatanPesertaFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * @return BelongsTo<TahunAjaran, $this>
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * @return BelongsTo<JenisUjian, $this>
     */
    public function jenisUjian(): BelongsTo
    {
        return $this->belongsTo(JenisUjian::class);
    }

    /**
     * @return BelongsTo<Ruang, $this>
     */
    public function ruang(): BelongsTo
    {
        return $this->belongsTo(Ruang::class);
    }

    /**
     * @return BelongsTo<Sesi, $this>
     */
    public function sesi(): BelongsTo
    {
        return $this->belongsTo(Sesi::class);
    }
}
