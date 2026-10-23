<?php

namespace App\Models;

use Database\Factories\JawabanSiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('jawaban_siswa')]
#[Fillable([
    'peserta_ujian_id',
    'soal_id',
    'jawaban',
    'ragu',
    'is_benar',
    'skor',
    'jumlah_putar_audio',
    'dijawab_pada',
])]
class JawabanSiswa extends Model
{
    /** @use HasFactory<JawabanSiswaFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jawaban' => 'array',
            'ragu' => 'boolean',
            'is_benar' => 'boolean',
            'skor' => 'decimal:2',
            'jumlah_putar_audio' => 'integer',
            'dijawab_pada' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PesertaUjian, $this>
     */
    public function pesertaUjian(): BelongsTo
    {
        return $this->belongsTo(PesertaUjian::class);
    }

    /**
     * @return BelongsTo<Soal, $this>
     */
    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
