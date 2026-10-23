<?php

namespace Database\Seeders;

use App\Models\Agama;
use Illuminate\Database\Seeder;

class AgamaSeeder extends Seeder
{
    /**
     * Seed the reference religions for local development.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        foreach ([
            'Islam',
            'Kristen',
            'Katolik',
            'Hindu',
            'Buddha',
            'Konghucu',
        ] as $nama) {
            $agama = Agama::withTrashed()->firstOrCreate([
                'nama' => $nama,
            ]);

            if ($agama->trashed()) {
                $agama->restore();
            }
        }
    }
}
