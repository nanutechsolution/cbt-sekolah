<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role')->default('guru')->after('password');
            $table->boolean('is_aktif')->default(true)->after('role');
            $table->string('email')->nullable()->change();
            $table->index(['role', 'is_aktif']);
        });

        DB::table('users')
            ->whereNull('username')
            ->orderBy('id')
            ->each(function (object $user): void {
                $base = Str::slug(Str::before((string) $user->email, '@')) ?: 'user'.$user->id;
                $username = $base;
                $suffix = 2;

                while (DB::table('users')->where('username', $username)->exists()) {
                    $username = $base.$suffix;
                    $suffix++;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'username' => $username,
                ]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'is_aktif']);
            $table->dropColumn(['username', 'role', 'is_aktif']);
        });

        DB::table('users')->whereNull('email')->update([
            'email' => DB::raw("CONCAT('user', id, '@example.invalid')"),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
