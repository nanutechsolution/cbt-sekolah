<?php

namespace Database\Factories;

use App\Enums\JenisPelanggaran;
use App\Models\PelanggaranUjian;
use App\Models\PesertaUjian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PelanggaranUjian>
 */
class PelanggaranUjianFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'peserta_ujian_id' => PesertaUjian::factory(),
            'jenis' => fake()->randomElement(JenisPelanggaran::cases()),
            'keterangan' => null,
        ];
    }
}
