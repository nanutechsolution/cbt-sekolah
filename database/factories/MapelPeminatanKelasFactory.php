<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\MapelPeminatanKelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapelPeminatanKelas>
 */
class MapelPeminatanKelasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kelas_id' => Kelas::factory(),
            'mapel_id' => Mapel::factory()->peminatan(),
        ];
    }
}
