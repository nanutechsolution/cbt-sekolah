<?php

namespace Database\Factories;

use App\Models\JenisUjian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JenisUjian>
 */
class JenisUjianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Jenis Ujian '.fake()->unique()->numberBetween(1, 99999),
            'kode' => 'JU'.fake()->unique()->numberBetween(1, 99999),
        ];
    }
}
