<?php

namespace Database\Factories;

use App\Models\Agama;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agama>
 */
class AgamaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Agama '.fake()->unique()->numberBetween(1, 99999),
        ];
    }
}
