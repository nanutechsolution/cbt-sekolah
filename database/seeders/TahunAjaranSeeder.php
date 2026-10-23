<?php

namespace Database\Seeders;

use App\Enums\Semester;
use App\Models\TahunAjaran;
use App\Services\TahunAjaranService;
use Illuminate\Database\Seeder;

class TahunAjaranSeeder extends Seeder
{
    /**
     * Seed the current academic year for local development.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $service = app(TahunAjaranService::class);

        if ($service->active()) {
            return;
        }

        $tahunMulai = now()->month >= 7 ? now()->year : now()->year - 1;
        $nama = $tahunMulai.'/'.($tahunMulai + 1);
        $semester = now()->month >= 7 ? Semester::Ganjil : Semester::Genap;

        $tahunAjaran = TahunAjaran::query()
            ->where('nama', $nama)
            ->where('semester', $semester)
            ->first();

        $service->save([
            'nama' => $nama,
            'semester' => $semester,
            'is_aktif' => true,
        ], $tahunAjaran);
    }
}
