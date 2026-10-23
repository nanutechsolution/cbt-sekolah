<?php

namespace Database\Factories;

use App\Enums\StatusJadwalUjian;
use App\Models\JadwalUjian;
use App\Models\PaketSoal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JadwalUjian>
 */
class JadwalUjianFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mulai = now()->addMinutes(fake()->numberBetween(5, 120));

        return [
            'paket_soal_id' => PaketSoal::factory()->siap(),
            'nama' => 'Ujian '.fake()->unique()->numberBetween(1, 99999),
            'mulai' => $mulai,
            'selesai' => $mulai->copy()->addHours(2),
            'durasi_menit' => 60,
            'status' => StatusJadwalUjian::Nonaktif,
            'dibuat_oleh' => User::factory()->admin(),
        ];
    }

    public function aktif(): static
    {
        return $this->state(fn (array $attributes): array => [
            'mulai' => now()->subMinute(),
            'selesai' => now()->addHours(2),
            'status' => StatusJadwalUjian::Aktif,
        ]);
    }

    public function selesai(): static
    {
        return $this->state(fn (array $attributes): array => [
            'mulai' => now()->subHours(2),
            'selesai' => now()->subHour(),
            'status' => StatusJadwalUjian::Selesai,
        ]);
    }
}
