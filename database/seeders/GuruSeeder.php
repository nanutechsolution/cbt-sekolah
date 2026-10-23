<?php

namespace Database\Seeders;

use App\Enums\JenisKelamin;
use App\Enums\UserRole;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Seeder;

class GuruSeeder extends Seeder
{
    /**
     * Create a teacher profile for the local teacher account.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $user = User::query()
            ->where('role', UserRole::Guru)
            ->orderBy('id')
            ->first();

        if (! $user) {
            return;
        }

        $guru = Guru::withTrashed()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'nip' => '198001012006041001',
                'nama' => $user->name,
                'jenis_kelamin' => JenisKelamin::LakiLaki->value,
                'no_hp' => '081234567890',
            ],
        );

        if ($guru->trashed()) {
            $guru->restore();
        }
    }
}
