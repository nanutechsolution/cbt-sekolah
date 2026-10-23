<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
        $this->call(PengaturanSekolahSeeder::class);

        if (app()->isLocal()) {
            $this->call(GuruUserSeeder::class);
            $this->call(TahunAjaranSeeder::class);
            $this->call(AgamaSeeder::class);
            $this->call(RuangSeeder::class);
            $this->call(SesiSeeder::class);
            $this->call(JenisUjianSeeder::class);
            $this->call(JurusanSeeder::class);
            $this->call(MapelSeeder::class);
            $this->call(GuruSeeder::class);
            $this->call(KelasSeeder::class);
            $this->call(MapelPeminatanSeeder::class);
            $this->call(SiswaSeeder::class);
        }
    }
}
