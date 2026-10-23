<?php

namespace Database\Seeders;

use App\Models\Ruang;
use Illuminate\Database\Seeder;

class RuangSeeder extends Seeder
{
    /**
     * Seed the exam rooms for local development.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        foreach ([
            ['nama' => 'Ruang 01', 'kapasitas' => 30],
            ['nama' => 'Ruang 02', 'kapasitas' => 30],
            ['nama' => 'Ruang 03', 'kapasitas' => 30],
        ] as $data) {
            $ruang = Ruang::withTrashed()->firstOrCreate(
                ['nama' => $data['nama']],
                ['kapasitas' => $data['kapasitas']],
            );

            if ($ruang->trashed()) {
                $ruang->restore();
            }
        }
    }
}
