<?php

namespace Database\Factories;

use App\Models\Sesi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sesi>
 */
class SesiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jamMulai = fake()->numberBetween(6, 20);

        return [
            'nama' => 'Sesi '.fake()->unique()->numberBetween(1, 99999),
            'jam_mulai' => sprintf('%02d:00', $jamMulai),
            'jam_selesai' => sprintf('%02d:00', $jamMulai + 1),
        ];
    }
}
