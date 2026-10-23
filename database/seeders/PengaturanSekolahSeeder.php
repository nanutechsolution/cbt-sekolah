<?php

namespace Database\Seeders;

use App\Services\PengaturanSekolahService;
use Illuminate\Database\Seeder;

class PengaturanSekolahSeeder extends Seeder
{
    /**
     * Seed the singleton school settings row.
     */
    public function run(): void
    {
        app(PengaturanSekolahService::class)->get();
    }
}
