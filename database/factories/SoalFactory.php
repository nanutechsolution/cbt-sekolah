<?php

namespace Database\Factories;

use App\Enums\TipeSoal;
use App\Models\PaketSoal;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Soal>
 */
class SoalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paket_soal_id' => PaketSoal::factory(),
            'nomor' => null,
            'tipe' => TipeSoal::Pg,
            'pertanyaan' => fake()->paragraph(),
            'gambar' => null,
            'audio' => null,
            'audio_maks_putar' => null,
            'bobot' => 1,
            'pembahasan' => null,
        ];
    }

    public function essay(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tipe' => TipeSoal::Essay,
        ]);
    }
}
