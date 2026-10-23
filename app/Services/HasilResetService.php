<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HasilResetService
{
    private const MASA_BERLAKU_MENIT = 10;

    /**
     * @param  array<string, mixed>  $data
     */
    public function simpan(int $aktorId, array $data): string
    {
        $token = Str::random(64);

        Cache::put(
            $this->kunci($token),
            [
                'aktor_id' => $aktorId,
                ...$data,
            ],
            now()->addMinutes(self::MASA_BERLAKU_MENIT),
        );

        return $token;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function ambil(string $token, int $aktorId): ?array
    {
        $data = Cache::pull($this->kunci($token));

        if (! is_array($data) || (int) ($data['aktor_id'] ?? 0) !== $aktorId) {
            return null;
        }

        return $data;
    }

    public function simpanPasswordSementara(
        int $aktorId,
        User $pengguna,
        string $passwordSementara,
        string $kembali,
    ): string {
        return $this->simpan($aktorId, [
            'judul' => 'Password Sementara',
            'subjudul' => "Password baru untuk akun {$pengguna->name} ({$pengguna->username}).",
            'kolom' => ['Nama', 'Username', 'Password Sementara'],
            'baris' => [[
                $pengguna->name,
                $pengguna->username,
                $passwordSementara,
            ]],
            'kolom_dilewati' => [],
            'dilewati' => [],
            'catatan' => 'Password ini hanya ditampilkan sekali. Catat atau berikan kepada pemilik akun sekarang. Password tidak dikirim melalui email.',
            'kembali' => $kembali,
        ]);
    }

    /**
     * @param  array{
     *     direset: array<int, array{siswa: Siswa, security_key: string}>,
     *     dilewati: array<int, array{siswa: Siswa, alasan: string}>
     * }  $hasil
     */
    public function simpanSecurityKey(
        int $aktorId,
        array $hasil,
        string $kembali,
        string $sumber = 'Reset security key',
    ): string {
        $baris = collect($hasil['direset'])
            ->map(fn (array $item): array => [
                $item['siswa']->nama,
                $item['siswa']->nis,
                $item['siswa']->username,
                $item['security_key'],
            ])
            ->all();

        $dilewati = collect($hasil['dilewati'])
            ->map(fn (array $item): array => [
                $item['siswa']->nama,
                $item['siswa']->nis,
                $item['alasan'],
            ])
            ->all();

        return $this->simpan($aktorId, [
            'judul' => 'Security Key Baru',
            'subjudul' => $sumber.'. Key baru hanya ditampilkan sekali.',
            'kolom' => ['Nama', 'NIS', 'Username', 'Security Key'],
            'baris' => $baris,
            'kolom_dilewati' => ['Nama', 'NIS', 'Alasan Dilewati'],
            'dilewati' => $dilewati,
            'catatan' => 'Key lama langsung tidak berlaku. Siswa yang sedang mengerjakan ujian tidak direset dan tetap memakai key lama.',
            'kembali' => $kembali,
        ]);
    }

    private function kunci(string $token): string
    {
        return 'hasil-reset:'.$token;
    }
}
