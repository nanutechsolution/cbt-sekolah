<?php

namespace Database\Factories;

use App\Models\JenisUjian;
use App\Models\PenempatanPeserta;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenempatanPeserta>
 */
class PenempatanPesertaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siswa_id' => Siswa::factory(),
            'tahun_ajaran_id' => TahunAjaran::factory(),
            'jenis_ujian_id' => JenisUjian::factory(),
            'ruang_id' => Ruang::factory(),
            'sesi_id' => Sesi::factory(),
            'no_peserta' => fake()->unique()->numerify('######'),
        ];
    }
}
