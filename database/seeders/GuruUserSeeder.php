<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GuruUserSeeder extends Seeder
{
    /**
     * Seed a local-only teacher account for panel testing.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $username = Str::lower((string) env('GURU_USERNAME', 'guru'));
        $password = env('GURU_PASSWORD');
        $password = filled($password) ? $password : 'password';
        $email = env('GURU_EMAIL');
        $email = filled($email) ? $email : null;

        User::updateOrCreate(
            ['username' => $username],
            [
                'name' => env('GURU_NAME', 'Guru Contoh'),
                'email' => $email,
                'password' => $password,
                'role' => UserRole::Guru,
                'is_aktif' => true,
            ],
        );

        $this->command?->info("Akun guru lokal siap: {$username} / {$password}");
    }
}
