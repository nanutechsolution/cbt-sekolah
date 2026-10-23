<?php

namespace App\Services;

use App\Enums\JenjangSekolah;
use App\Models\PengaturanSekolah;

class PengaturanSekolahService
{
    public function find(): ?PengaturanSekolah
    {
        return PengaturanSekolah::query()->first();
    }

    public function get(): PengaturanSekolah
    {
        return PengaturanSekolah::query()->firstOrCreate([], $this->defaults());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): PengaturanSekolah
    {
        $pengaturanSekolah = $this->get();

        $pengaturanSekolah->update($data);

        return $pengaturanSekolah->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'nama_sekolah' => 'NANU TECH SOLUTIONS',
            'jenjang' => JenjangSekolah::Sd,
            'npsn' => null,
            'alamat' => null,
            'kota' => null,
            'logo' => null,
            'nama_kepala_sekolah' => null,
            'nip_kepala_sekolah' => null,
            'batas_pelanggaran' => 5,
            'auto_tangguhkan' => true,
            'idle_timeout_menit' => 10,
        ];
    }
}
