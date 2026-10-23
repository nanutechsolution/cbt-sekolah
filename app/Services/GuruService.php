<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuruService
{
    /**
     * Create a teacher profile together with its login account.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Guru
    {
        return DB::transaction(function () use ($data): Guru {
            $user = User::query()->create([
                'name' => $data['nama'],
                'username' => Str::lower(trim($data['username'])),
                'email' => filled($data['email'] ?? null) ? Str::lower(trim($data['email'])) : null,
                'password' => $data['password'],
                'role' => UserRole::Guru,
                'is_aktif' => (bool) ($data['is_aktif'] ?? true),
            ]);

            return Guru::query()->create([
                'user_id' => $user->id,
                'nip' => filled($data['nip'] ?? null) ? trim($data['nip']) : null,
                'nama' => $data['nama'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'no_hp' => filled($data['no_hp'] ?? null) ? trim($data['no_hp']) : null,
            ]);
        });
    }

    /**
     * Update both the teacher profile and its login account.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Guru $guru, array $data): Guru
    {
        return DB::transaction(function () use ($guru, $data): Guru {
            $userData = [
                'name' => $data['nama'],
                'username' => Str::lower(trim($data['username'])),
                'email' => filled($data['email'] ?? null) ? Str::lower(trim($data['email'])) : null,
                'is_aktif' => (bool) ($data['is_aktif'] ?? true),
            ];

            if (filled($data['password'] ?? null)) {
                $userData['password'] = $data['password'];
            }

            $guru->user->update($userData);

            $guru->update([
                'nip' => filled($data['nip'] ?? null) ? trim($data['nip']) : null,
                'nama' => $data['nama'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'no_hp' => filled($data['no_hp'] ?? null) ? trim($data['no_hp']) : null,
            ]);

            return $guru->refresh();
        });
    }
}
