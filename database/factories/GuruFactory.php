<?php

namespace Database\Factories;

use App\Enums\JenisKelamin;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guru>
 */
class GuruFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->guru()->create();

        return [
            'user_id' => $user->id,
            'nip' => fake()->unique()->numerify('19##############'),
            'nama' => $user->name,
            'jenis_kelamin' => fake()->randomElement(JenisKelamin::cases())->value,
            'no_hp' => fake()->numerify('08##########'),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
            'nama' => $user->name,
        ]);
    }

    public function perempuan(): static
    {
        return $this->state(fn (array $attributes): array => [
            'jenis_kelamin' => JenisKelamin::Perempuan->value,
        ]);
    }
}
