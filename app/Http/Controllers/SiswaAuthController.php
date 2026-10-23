<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SiswaAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('siswa')->check()) {
            return redirect()->route('siswa.dashboard');
        }

        return view('siswa.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'security_key' => ['required', 'string', 'max:100'],
        ]);

        $username = Str::lower(trim($data['username']));
        $securityKey = trim($data['security_key']);
        $rateLimitKey = 'siswa-login:'.Str::lower($request->ip().'|'.$username);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            throw ValidationException::withMessages([
                'username' => 'Terlalu banyak percobaan masuk. Coba lagi setelah satu menit.',
            ]);
        }

        $siswa = Siswa::query()
            ->where('username', $username)
            ->where('is_aktif', true)
            ->first();

        if (! $siswa || ! hash_equals((string) $siswa->security_key, $securityKey)) {
            RateLimiter::hit($rateLimitKey, 60);

            throw ValidationException::withMessages([
                'username' => 'Username atau security key salah.',
            ]);
        }

        RateLimiter::clear($rateLimitKey);

        Auth::guard('siswa')->login($siswa);
        $request->session()->regenerate();

        return redirect()->intended(route('siswa.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('siswa')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('siswa.login');
    }
}
