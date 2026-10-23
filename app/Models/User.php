<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'is_aktif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_aktif' => 'boolean',
            'harus_ganti_password' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (User $user): void {
            if (! $user->isDirty('is_aktif') || $user->is_aktif) {
                return;
            }

            if (! (bool) $user->getRawOriginal('is_aktif')) {
                return;
            }

            if ($user->getRawOriginal('role') !== UserRole::Admin->value) {
                return;
            }

            if ($user->adaAdminAktifLain()) {
                return;
            }

            throw ValidationException::withMessages([
                'is_aktif' => 'Admin aktif terakhir tidak boleh dinonaktifkan.',
            ]);
        });
    }

    /**
     * @return HasOne<Guru, $this>
     */
    public function guru(): HasOne
    {
        return $this->hasOne(Guru::class);
    }

    /**
     * @return HasMany<JadwalUjian, $this>
     */
    public function jadwalUjianDibuat(): HasMany
    {
        return $this->hasMany(JadwalUjian::class, 'dibuat_oleh');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_aktif) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->role === UserRole::Admin,
            'guru' => $this->role === UserRole::Guru,
            default => false,
        };
    }

    public function adaAdminAktifLain(): bool
    {
        return static::query()
            ->whereKeyNot($this->getKey())
            ->where('role', UserRole::Admin->value)
            ->where('is_aktif', true)
            ->exists();
    }

    public function adminAktifTerakhir(): bool
    {
        return $this->role === UserRole::Admin
            && $this->is_aktif
            && ! $this->adaAdminAktifLain();
    }

    protected function username(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::lower($value),
        );
    }
}
