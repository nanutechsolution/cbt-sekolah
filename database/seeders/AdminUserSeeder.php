<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the application's administrator account.
     */
    public function run(): void
    {
        $username = Str::lower((string) env('ADMIN_USERNAME', 'admin'));
        $password = env('ADMIN_PASSWORD');

        if (blank($password)) {
            if (app()->isProduction()) {
                throw new RuntimeException('ADMIN_PASSWORD wajib diisi di environment production.');
            }

            $password = 'password';
        }

        $email = env('ADMIN_EMAIL');
        $email = filled($email) ? $email : null;

        User::updateOrCreate(
            ['username' => $username],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'email' => $email,
                'password' => $password,
                'role' => UserRole::Admin,
                'is_aktif' => true,
            ],
        );

        if (app()->isLocal()) {
            $this->command?->info("Akun admin siap: {$username} / {$password}");
        }
    }
}
