<?php

namespace Database\Factories;

use App\Enums\StatusPaketSoal;
use App\Models\JenisUjian;
use App\Models\Mapel;
use App\Models\PaketSoal;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaketSoal>
 */
class PaketSoalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 99999);

        return [
            'kode' => 'PS'.$number,
            'nama' => 'Paket Soal '.$number,
            'mapel_id' => Mapel::factory(),
            'jenis_ujian_id' => JenisUjian::factory(),
            'tahun_ajaran_id' => TahunAjaran::factory(),
            'tingkat' => fake()->numberBetween(1, 12),
            'dibuat_oleh' => User::factory()->admin(),
            'acak_soal' => true,
            'acak_opsi' => true,
            'tampilkan_nilai' => true,
            'status' => StatusPaketSoal::Draft,
        ];
    }

    public function siap(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StatusPaketSoal::Siap,
        ]);
    }
}
