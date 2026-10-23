<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    /**
     * Seed local sample classes and teaching assignments.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $tkj = Jurusan::query()->where('kode', 'TKJ')->first();
        $guru = Guru::query()->first();
        $tahunAjaran = TahunAjaran::query()->where('is_aktif', true)->first();

        $classes = [
            ['nama' => '7A', 'tingkat' => 7, 'jurusan_id' => null],
            ['nama' => '8A', 'tingkat' => 8, 'jurusan_id' => null],
            ['nama' => '9A', 'tingkat' => 9, 'jurusan_id' => null],
            ['nama' => 'X TKJ 1', 'tingkat' => 10, 'jurusan_id' => $tkj?->id],
            ['nama' => 'XI TKJ 1', 'tingkat' => 11, 'jurusan_id' => $tkj?->id],
            ['nama' => 'XII TKJ 1', 'tingkat' => 12, 'jurusan_id' => $tkj?->id],
        ];

        foreach ($classes as $data) {
            $kelas = Kelas::withTrashed()->firstOrCreate(
                ['nama' => $data['nama']],
                [
                    ...$data,
                    'wali_kelas_id' => $guru?->id,
                ],
            );

            if ($kelas->trashed()) {
                $kelas->restore();
            }
        }

        if (! $guru || ! $tahunAjaran) {
            return;
        }

        foreach (['MTK', 'BIN', 'TIK'] as $kodeMapel) {
            $mapel = Mapel::query()->where('kode', $kodeMapel)->first();

            if (! $mapel) {
                continue;
            }

            foreach (Kelas::query()->whereIn('nama', ['X TKJ 1', 'XI TKJ 1', 'XII TKJ 1'])->get() as $kelas) {
                $guru->mengajar()->firstOrCreate([
                    'kelas_id' => $kelas->id,
                    'mapel_id' => $mapel->id,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                ]);
            }
        }
    }
}
