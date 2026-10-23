<?php

namespace Database\Factories;

use App\Enums\JenisKelamin;
use App\Models\Agama;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Siswa>
 */
class SiswaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nis' => fake()->unique()->numerify('##########'),
            'nisn' => fake()->boolean(70) ? fake()->unique()->numerify('##########') : null,
            'nama' => fake()->name(),
            'jenis_kelamin' => fake()->randomElement(JenisKelamin::cases())->value,
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->dateTimeBetween('-19 years', '-10 years')->format('Y-m-d'),
            'agama_id' => Agama::factory(),
            'kelas_id' => Kelas::factory(),
            'foto' => null,
            'username' => Str::lower(fake()->unique()->userName()),
            'security_key' => Str::upper(Str::random(10)),
            'is_aktif' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_aktif' => false,
        ]);
    }
}
