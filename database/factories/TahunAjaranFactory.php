<?php

namespace Database\Factories;

use App\Enums\Semester;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahunAjaran>
 */
class TahunAjaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tahunMulai = fake()->unique()->numberBetween(2000, 2100);

        return [
            'nama' => $tahunMulai.'/'.($tahunMulai + 1),
            'semester' => fake()->randomElement(Semester::cases()),
            'is_aktif' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_aktif' => true,
        ]);
    }
}
