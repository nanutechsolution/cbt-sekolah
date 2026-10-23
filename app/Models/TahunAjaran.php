<?php

namespace App\Models;

use App\Enums\Semester;
use Database\Factories\TahunAjaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('tahun_ajaran')]
#[Fillable(['nama', 'semester', 'is_aktif'])]
class TahunAjaran extends Model
{
    /** @use HasFactory<TahunAjaranFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semester' => Semester::class,
            'is_aktif' => 'boolean',
        ];
    }
}
