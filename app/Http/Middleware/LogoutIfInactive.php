<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\PengaturanSekolahService;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogoutIfInactive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()) {
            return $next($request);
        }

        $user = Auth::guard('web')->user();

        if ($user instanceof User && ! $user->is_aktif) {
            return $this->logout(
                $request,
                'Akun Anda sudah dinonaktifkan. Silakan hubungi admin sekolah.',
            );
        }

        $batasMenit = max(1, (int) app(PengaturanSekolahService::class)->get()->idle_timeout_menit);
        $terakhir = $request->session()->get('panel_last_activity');

        if (is_numeric($terakhir) && now()->timestamp - (int) $terakhir >= $batasMenit * 60) {
            return $this->logout(
                $request,
                'Sesi Anda berakhir karena tidak aktif selama '.$batasMenit.' menit.',
            );
        }

        $request->session()->put('panel_last_activity', now()->timestamp);

        return $next($request);
    }

    private function logout(Request $request, string $pesan): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $loginUrl = Filament::getCurrentPanel()?->getLoginUrl();

        if (blank($loginUrl)) {
            $loginUrl = $request->is('guru', 'guru/*')
                ? url('/guru/login')
                : url('/admin/login');
        }

        return redirect()->to($loginUrl)->with('status', $pesan);
    }
}
