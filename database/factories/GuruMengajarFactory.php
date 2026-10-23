<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuruMengajar>
 */
class GuruMengajarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guru_id' => Guru::factory(),
            'kelas_id' => Kelas::factory(),
            'mapel_id' => Mapel::factory(),
            'tahun_ajaran_id' => TahunAjaran::factory(),
        ];
    }
}
