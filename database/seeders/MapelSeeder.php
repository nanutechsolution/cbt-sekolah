<?php

namespace Database\Seeders;

use App\Models\Mapel;
use Illuminate\Database\Seeder;

class MapelSeeder extends Seeder
{
    /**
     * Seed local sample subjects.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        foreach ([
            ['kode' => 'MTK', 'nama' => 'Matematika', 'kelompok' => 'Umum', 'is_peminatan' => false],
            ['kode' => 'BIN', 'nama' => 'Bahasa Indonesia', 'kelompok' => 'Umum', 'is_peminatan' => false],
            ['kode' => 'BIG', 'nama' => 'Bahasa Inggris', 'kelompok' => 'Umum', 'is_peminatan' => false],
            ['kode' => 'PAI', 'nama' => 'Pendidikan Agama Islam', 'kelompok' => 'Umum', 'is_peminatan' => false],
            ['kode' => 'FIS', 'nama' => 'Fisika', 'kelompok' => 'Kejuruan', 'is_peminatan' => true],
            ['kode' => 'KIM', 'nama' => 'Kimia', 'kelompok' => 'Kejuruan', 'is_peminatan' => true],
            ['kode' => 'TIK', 'nama' => 'Teknologi Informasi dan Komunikasi', 'kelompok' => 'Kejuruan', 'is_peminatan' => false],
        ] as $data) {
            $mapel = Mapel::withTrashed()->firstOrCreate(
                ['kode' => $data['kode']],
                $data,
            );

            if ($mapel->trashed()) {
                $mapel->restore();
            }
        }
    }
}
