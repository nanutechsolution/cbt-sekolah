<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiswaAktif
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $siswa = Auth::guard('siswa')->user();

        if (! $siswa || ! $siswa->is_aktif) {
            Auth::guard('siswa')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('siswa.login')
                ->withErrors(['username' => 'Sesi Anda berakhir. Silakan masuk kembali.']);
        }

        return $next($request);
    }
}
