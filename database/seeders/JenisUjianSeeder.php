<?php

namespace Database\Seeders;

use App\Models\JenisUjian;
use Illuminate\Database\Seeder;

class JenisUjianSeeder extends Seeder
{
    /**
     * Seed the exam types for local development.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        foreach ([
            ['kode' => 'UTS', 'nama' => 'Ujian Tengah Semester'],
            ['kode' => 'UAS', 'nama' => 'Ujian Akhir Semester'],
            ['kode' => 'PAS', 'nama' => 'Penilaian Akhir Semester'],
        ] as $data) {
            $jenisUjian = JenisUjian::withTrashed()->firstOrCreate(
                ['kode' => $data['kode']],
                ['nama' => $data['nama']],
            );

            if ($jenisUjian->trashed()) {
                $jenisUjian->restore();
            }
        }
    }
}
