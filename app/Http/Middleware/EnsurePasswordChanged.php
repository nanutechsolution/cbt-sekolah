<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user instanceof User || ! $user->is_aktif || ! $user->harus_ganti_password) {
            return $next($request);
        }

        if ($this->diizinkan($request)) {
            return $next($request);
        }

        $profilUrl = Filament::getCurrentPanel()?->getProfileUrl();

        if (blank($profilUrl)) {
            $profilUrl = $user->role === UserRole::Admin
                ? route('filament.admin.auth.profile')
                : route('filament.guru.auth.profile');
        }

        return redirect()
            ->to($profilUrl)
            ->with('status', 'Anda wajib mengganti password sebelum melanjutkan.');
    }

    private function diizinkan(Request $request): bool
    {
        return $request->routeIs('filament.*.auth.profile', 'filament.*.auth.logout');
    }
}
