<?php

namespace App\Models;

use App\Enums\JenjangSekolah;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('pengaturan_sekolah')]
#[Fillable([
    'nama_sekolah',
    'jenjang',
    'npsn',
    'alamat',
    'kota',
    'logo',
    'nama_kepala_sekolah',
    'nip_kepala_sekolah',
    'batas_pelanggaran',
    'auto_tangguhkan',
    'idle_timeout_menit',
])]
class PengaturanSekolah extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenjang' => JenjangSekolah::class,
            'batas_pelanggaran' => 'integer',
            'auto_tangguhkan' => 'boolean',
            'idle_timeout_menit' => 'integer',
        ];
    }
}
