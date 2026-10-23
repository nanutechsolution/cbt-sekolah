<?php

namespace Database\Factories;

use App\Models\Mapel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mapel>
 */
class MapelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 99999);

        return [
            'kode' => 'MPL'.$number,
            'nama' => 'Mata Pelajaran '.$number,
            'kelompok' => fake()->randomElement(['Umum', 'Kejuruan']),
            'is_peminatan' => false,
        ];
    }

    public function peminatan(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_peminatan' => true,
        ]);
    }
}
