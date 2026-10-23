<?php

namespace Database\Seeders;

use App\Models\Agama;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\Seeder;

class SiswaSeeder extends Seeder
{
    /**
     * Seed local sample students with printable demo security keys.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $kelas = Kelas::query()->where('nama', 'X TKJ 1')->first()
            ?? Kelas::query()->first();

        if (! $kelas) {
            return;
        }

        $agama = Agama::query()->where('nama', 'Islam')->first();

        foreach (range(1, 10) as $index) {
            $nis = '2026'.str_pad((string) $index, 4, '0', STR_PAD_LEFT);
            $siswa = Siswa::withTrashed()->firstOrCreate(
                ['nis' => $nis],
                [
                    'nisn' => '0099'.str_pad((string) $index, 6, '0', STR_PAD_LEFT),
                    'nama' => 'Siswa Demo '.$index,
                    'jenis_kelamin' => $index % 2 === 0 ? 'P' : 'L',
                    'tempat_lahir' => 'Bandung',
                    'tanggal_lahir' => '2008-01-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                    'agama_id' => $agama?->id,
                    'kelas_id' => $kelas->id,
                    'username' => 'siswa'.$index,
                    'security_key' => 'DEMO'.str_pad((string) $index, 6, '0', STR_PAD_LEFT),
                    'is_aktif' => true,
                ],
            );

            if ($siswa->trashed()) {
                $siswa->restore();
            }
        }
    }
}
