<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_aktif) {
            abort(403);
        }

        $expectedRole = UserRole::tryFrom($role);

        if (! $expectedRole || $user->role !== $expectedRole) {
            abort(403);
        }

        return $next($request);
    }
}
