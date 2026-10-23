<?php

namespace Database\Factories;

use App\Models\OpsiSoal;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpsiSoal>
 */
class OpsiSoalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'soal_id' => Soal::factory(),
            'label' => 'A',
            'teks' => fake()->sentence(),
            'gambar' => null,
            'is_benar' => false,
            'urutan' => 1,
            'pasangan' => null,
        ];
    }

    public function benar(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_benar' => true,
        ]);
    }
}
