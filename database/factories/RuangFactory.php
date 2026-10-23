<?php

namespace Database\Factories;

use App\Models\Ruang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ruang>
 */
class RuangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Ruang '.fake()->unique()->numberBetween(1, 99999),
            'kapasitas' => fake()->numberBetween(20, 40),
        ];
    }

    public function withoutKapasitas(): static
    {
        return $this->state(fn (array $attributes): array => [
            'kapasitas' => null,
        ]);
    }
}
