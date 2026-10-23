<?php

namespace App\Services;

use App\Enums\JenisAksiLog;
use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AkunService
{
    private const KARAKTER_PASSWORD_SEMENTARA = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';

    public function __construct(
        private readonly LogAktivitasService $logAktivitasService,
    ) {}

    /**
     * @return array<int, mixed>
     */
    public function passwordRules(
        bool $wajib = true,
        ?string $username = null,
        ?string $name = null,
    ): array {
        $rules = [
            $wajib ? 'required' : 'nullable',
            'string',
            'min:10',
            'max:255',
            function (string $attribute, mixed $value, Closure $fail) use ($username, $name): void {
                if (! is_string($value) || trim($value) === '') {
                    return;
                }

                $password = Str::lower(trim($value));

                foreach (array_filter([$username, $name]) as $terlarang) {
                    if ($password === Str::lower(trim((string) $terlarang))) {
                        $fail('Password tidak boleh sama dengan username atau nama.');

                        return;
                    }
                }
            },
        ];

        return $rules;
    }

    public function validasiPassword(string $password, ?string $username = null, ?string $name = null): void
    {
        Validator::make(
            ['password' => $password],
            ['password' => $this->passwordRules(true, $username, $name)],
            [
                'password.required' => 'Password wajib diisi.',
                'password.min' => 'Password minimal 10 karakter.',
                'password.max' => 'Password maksimal 255 karakter.',
            ],
        )->validate();
    }

    public function buatPasswordSementara(?string $username = null, ?string $name = null): string
    {
        $panjang = strlen(self::KARAKTER_PASSWORD_SEMENTARA);

        do {
            $password = '';

            for ($i = 0; $i < 16; $i++) {
                $password .= self::KARAKTER_PASSWORD_SEMENTARA[random_int(0, $panjang - 1)];
            }

            $punyaHurufBesar = preg_match('/[A-Z]/', $password) === 1;
            $punyaHurufKecil = preg_match('/[a-z]/', $password) === 1;
            $punyaAngka = preg_match('/[0-9]/', $password) === 1;
            $samaDenganIdentitas = collect([$username, $name])
                ->filter()
                ->contains(fn (string $terlarang): bool => Str::lower(trim($terlarang)) === Str::lower($password));
        } while (! $punyaHurufBesar || ! $punyaHurufKecil || ! $punyaAngka || $samaDenganIdentitas);

        return $password;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function buatAdmin(array $data, User $aktor): User
    {
        $this->pastikanAdmin($aktor);

        $data = $this->normalisasiDataAkun($data);

        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->passwordRules(true, $data['username'], $data['name']),
        ], [
            'username.unique' => 'Username sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'email.unique' => 'Email sudah digunakan.',
            'password.min' => 'Password minimal 10 karakter.',
        ])->validate();

        return DB::transaction(function () use ($data, $aktor): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'role' => UserRole::Admin,
                'is_aktif' => true,
            ]);

            $user->forceFill(['harus_ganti_password' => true])->save();

            $this->logAktivitasService->catat(
                JenisAksiLog::BuatAkun,
                $user,
                $aktor,
                'Membuat akun admin baru.',
                null,
                $this->dataAkunAman($user),
            );

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function buatAkunGuru(array $data, ?User $aktor = null): User
    {
        if ($aktor !== null) {
            $this->pastikanAdmin($aktor);
        }

        $data = $this->normalisasiDataAkun($data);

        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->passwordRules(true, $data['username'], $data['name']),
        ], [
            'username.unique' => 'Username sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'email.unique' => 'Email sudah digunakan.',
            'password.min' => 'Password minimal 10 karakter.',
        ])->validate();

        return DB::transaction(function () use ($data, $aktor): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'role' => UserRole::Guru,
                'is_aktif' => (bool) ($data['is_aktif'] ?? true),
            ]);

            $user->forceFill(['harus_ganti_password' => true])->save();

            $this->logAktivitasService->catat(
                JenisAksiLog::BuatAkun,
                $user,
                $aktor,
                'Membuat akun guru baru.',
                null,
                $this->dataAkunAman($user),
            );

            return $user;
        });
    }

    /**
     * Profil hanya boleh mengubah nama dan password. Password pada parameter
     * ini sudah dalam bentuk hash dari form profil.
     *
     * @param  array<string, mixed>  $data
     */
    public function perbaruiProfil(User $user, array $data): User
    {
        $name = trim((string) ($data['name'] ?? ''));
        $passwordBaru = $data['password'] ?? null;
        $passwordSaatIni = $data['currentPassword'] ?? null;

        Validator::make(['name' => $name], [
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 255 karakter.',
        ])->validate();

        if (filled($passwordBaru)) {
            if (! is_string($passwordSaatIni) || ! Hash::check($passwordSaatIni, $user->getAuthPassword())) {
                throw ValidationException::withMessages([
                    'currentPassword' => 'Password saat ini tidak sesuai.',
                ]);
            }

            if (! is_string($passwordBaru)) {
                throw new InvalidArgumentException('Format password baru tidak valid.');
            }

            if (! Hash::isHashed($passwordBaru)) {
                $this->validasiPassword($passwordBaru, $user->username, $name);
                $passwordBaru = Hash::make($passwordBaru);
            }
        }

        return DB::transaction(function () use ($user, $name, $passwordBaru): User {
            $sebelum = [
                'name' => $user->name,
                'harus_ganti_password' => $user->harus_ganti_password,
            ];

            $user->name = $name;

            if (filled($passwordBaru)) {
                $user->password = $passwordBaru;
                $user->harus_ganti_password = false;
            }

            $user->save();

            $this->logAktivitasService->catat(
                JenisAksiLog::UbahProfil,
                $user,
                $user,
                filled($passwordBaru) ? 'Mengubah nama dan password sendiri.' : 'Mengubah nama sendiri.',
                $sebelum,
                [
                    'name' => $user->name,
                    'harus_ganti_password' => $user->harus_ganti_password,
                ],
            );

            return $user->refresh();
        });
    }

    public function resetPassword(User $target, ?User $aktor = null, bool $darurat = false): string
    {
        if ($aktor !== null) {
            $this->pastikanAdmin($aktor);
        } elseif (! $darurat || ! app()->runningInConsole()) {
            throw new AuthorizationException('Reset password tanpa admin hanya diizinkan melalui perintah artisan darurat.');
        }

        if (! in_array($target->role, [UserRole::Admin, UserRole::Guru], true)) {
            throw new InvalidArgumentException('Hanya akun admin atau guru yang dapat direset.');
        }

        return DB::transaction(function () use ($target, $aktor, $darurat): string {
            /** @var User $target */
            $target = User::query()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();
            $passwordSementara = $this->buatPasswordSementara($target->username, $target->name);

            $sebelum = [
                'harus_ganti_password' => $target->harus_ganti_password,
                'is_aktif' => $target->is_aktif,
            ];

            $target->password = $passwordSementara;
            $target->harus_ganti_password = true;
            $target->save();

            $this->logAktivitasService->catat(
                JenisAksiLog::ResetPassword,
                $target,
                $aktor,
                $darurat ? 'Reset password darurat melalui artisan.' : 'Reset password oleh admin.',
                $sebelum,
                [
                    'harus_ganti_password' => true,
                    'is_aktif' => $target->is_aktif,
                ],
                penggunaNama: $aktor === null ? 'CLI (artisan)' : null,
            );

            return $passwordSementara;
        });
    }

    public function ubahStatusAktif(User $target, bool $aktif, User $aktor): User
    {
        $this->pastikanAdmin($aktor);

        if (! $aktif && $target->is($aktor)) {
            throw ValidationException::withMessages([
                'is_aktif' => 'Anda tidak dapat menonaktifkan akun sendiri.',
            ]);
        }

        return DB::transaction(function () use ($target, $aktif, $aktor): User {
            /** @var User $target */
            $target = User::query()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();

            if ($target->is_aktif === $aktif) {
                return $target;
            }

            if (! $aktif && $target->role === UserRole::Admin) {
                $adminAktif = User::query()
                    ->where('role', UserRole::Admin->value)
                    ->where('is_aktif', true)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($adminAktif->whereNotIn('id', [$target->getKey()])->isEmpty()) {
                    throw ValidationException::withMessages([
                        'is_aktif' => 'Admin aktif terakhir tidak boleh dinonaktifkan.',
                    ]);
                }
            }

            $sebelum = ['is_aktif' => $target->is_aktif];

            $target->is_aktif = $aktif;
            $target->save();

            $this->logAktivitasService->catat(
                $aktif ? JenisAksiLog::AktifkanAkun : JenisAksiLog::NonaktifkanAkun,
                $target,
                $aktor,
                $aktif ? 'Mengaktifkan akun.' : 'Menonaktifkan akun.',
                $sebelum,
                ['is_aktif' => $target->is_aktif],
            );

            return $target->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalisasiDataAkun(array $data): array
    {
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['username'] = Str::lower(trim((string) ($data['username'] ?? '')));
        $data['email'] = filled($data['email'] ?? null) ? Str::lower(trim((string) $data['email'])) : null;

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function dataAkunAman(User $user): array
    {
        return [
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role->value,
            'is_aktif' => $user->is_aktif,
            'harus_ganti_password' => $user->harus_ganti_password,
        ];
    }

    private function pastikanAdmin(User $aktor): void
    {
        if (! $aktor->is_aktif || $aktor->role !== UserRole::Admin) {
            throw new AuthorizationException('Hanya admin aktif yang dapat mengelola akun.');
        }
    }
}
