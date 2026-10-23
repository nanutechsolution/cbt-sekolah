<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use Illuminate\Database\Seeder;

class JurusanSeeder extends Seeder
{
    /**
     * Seed local sample departments.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        foreach ([
            'TKJ' => 'Teknik Komputer dan Jaringan',
            'AKL' => 'Akuntansi dan Keuangan Lembaga',
            'IPA' => 'Ilmu Pengetahuan Alam',
            'IPS' => 'Ilmu Pengetahuan Sosial',
        ] as $kode => $nama) {
            $jurusan = Jurusan::withTrashed()->firstOrCreate(
                ['kode' => $kode],
                ['nama' => $nama],
            );

            if ($jurusan->trashed()) {
                $jurusan->restore();
            }
        }
    }
}
