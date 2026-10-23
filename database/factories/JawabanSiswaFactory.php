<?php

namespace Database\Factories;

use App\Models\JawabanSiswa;
use App\Models\PesertaUjian;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JawabanSiswa>
 */
class JawabanSiswaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'peserta_ujian_id' => PesertaUjian::factory(),
            'soal_id' => Soal::factory(),
            'jawaban' => null,
            'ragu' => false,
            'is_benar' => null,
            'skor' => null,
            'jumlah_putar_audio' => 0,
            'dijawab_pada' => now(),
        ];
    }
}
