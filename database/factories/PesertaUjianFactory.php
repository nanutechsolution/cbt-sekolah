<?php

namespace Database\Factories;

use App\Enums\StatusPesertaUjian;
use App\Models\JadwalUjian;
use App\Models\PesertaUjian;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PesertaUjian>
 */
class PesertaUjianFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jadwal_ujian_id' => JadwalUjian::factory(),
            'siswa_id' => Siswa::factory(),
            'status' => StatusPesertaUjian::Belum,
            'jumlah_pelanggaran' => 0,
            'urutan_soal' => null,
            'urutan_opsi' => null,
        ];
    }

    public function mengerjakan(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StatusPesertaUjian::Mengerjakan,
            'waktu_mulai' => now()->subMinutes(5),
            'waktu_batas' => now()->addMinutes(55),
            'device_token' => 'test-device-token',
        ]);
    }

    public function selesai(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StatusPesertaUjian::Selesai,
            'waktu_mulai' => now()->subHour(),
            'waktu_batas' => now(),
            'waktu_selesai' => now(),
        ]);
    }
}
