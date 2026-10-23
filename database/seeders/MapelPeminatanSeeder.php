<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Mapel;
use Illuminate\Database\Seeder;

class MapelPeminatanSeeder extends Seeder
{
    /**
     * Seed local sample elective subjects for grade 11 and 12 classes.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $mapel = Mapel::query()
            ->whereIn('kode', ['FIS', 'KIM'])
            ->pluck('id');

        foreach (Kelas::query()->whereIn('tingkat', [11, 12])->get() as $kelas) {
            $kelas->mapelPeminatan()->syncWithoutDetaching($mapel->all());
        }
    }
}
