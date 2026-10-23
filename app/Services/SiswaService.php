<?php

namespace App\Services;

use App\Models\Siswa;
use Illuminate\Support\Str;

class SiswaService
{
    /**
     * Create a student and generate a printable security key when omitted.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Siswa
    {
        $data['username'] = Str::lower(trim($data['username']));
        $data['security_key'] = filled($data['security_key'] ?? null)
            ? trim($data['security_key'])
            : $this->generateSecurityKey();

        return Siswa::query()->create($data);
    }

    /**
     * Update a student without replacing an existing security key by accident.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Siswa $siswa, array $data): Siswa
    {
        $data['username'] = Str::lower(trim($data['username']));

        if (blank($data['security_key'] ?? null)) {
            unset($data['security_key']);
        } else {
            $data['security_key'] = trim($data['security_key']);
        }

        $siswa->update($data);

        return $siswa->refresh();
    }

    public function generateSecurityKey(): string
    {
        return Str::upper(Str::random(10));
    }
}
