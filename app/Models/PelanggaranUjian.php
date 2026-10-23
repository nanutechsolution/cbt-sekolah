<?php

namespace App\Models;

use App\Enums\JenisPelanggaran;
use Database\Factories\PelanggaranUjianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('pelanggaran_ujian')]
#[Fillable(['peserta_ujian_id', 'jenis', 'keterangan'])]
class PelanggaranUjian extends Model
{
    /** @use HasFactory<PelanggaranUjianFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => JenisPelanggaran::class,
        ];
    }

    /**
     * @return BelongsTo<PesertaUjian, $this>
     */
    public function pesertaUjian(): BelongsTo
    {
        return $this->belongsTo(PesertaUjian::class);
    }
}
