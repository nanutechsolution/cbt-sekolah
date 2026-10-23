<?php

namespace App\Models;

use Database\Factories\GuruMengajarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('guru_mengajar')]
#[Fillable(['guru_id', 'kelas_id', 'mapel_id', 'tahun_ajaran_id'])]
class GuruMengajar extends Model
{
    /** @use HasFactory<GuruMengajarFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Guru, $this>
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    /**
     * @return BelongsTo<Kelas, $this>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * @return BelongsTo<Mapel, $this>
     */
    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class);
    }

    /**
     * @return BelongsTo<TahunAjaran, $this>
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }
}
