<?php

namespace Database\Factories;

use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tingkat = fake()->numberBetween(1, 12);
        $number = fake()->unique()->numberBetween(1, 99999);

        return [
            'nama' => $tingkat.'-'.$number,
            'tingkat' => $tingkat,
            'jurusan_id' => null,
            'wali_kelas_id' => null,
        ];
    }

    public function forJurusan(Jurusan $jurusan): static
    {
        return $this->state(fn (array $attributes): array => [
            'jurusan_id' => $jurusan->id,
        ]);
    }

    public function tingkat(int $tingkat): static
    {
        return $this->state(fn (array $attributes): array => [
            'tingkat' => $tingkat,
        ]);
    }
}
