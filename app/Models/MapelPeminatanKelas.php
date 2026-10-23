<?php

namespace App\Models;

use Database\Factories\MapelPeminatanKelasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('mapel_peminatan_kelas')]
#[Fillable(['kelas_id', 'mapel_id'])]
class MapelPeminatanKelas extends Model
{
    /** @use HasFactory<MapelPeminatanKelasFactory> */
    use HasFactory;

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
}
