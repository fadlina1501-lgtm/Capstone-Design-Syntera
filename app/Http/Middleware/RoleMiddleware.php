<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if (strtolower((string) $user->status_account) !== 'active') {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['username' => 'Akun tidak aktif. Hubungi administrator.']);
        }

        $requested = collect($roles)
            ->flatMap(fn ($role) => explode('|', $role))
            ->map(fn ($role) => Str::lower(trim($role)))
            ->filter()
            ->values()
            ->all();
        $actual = Str::lower(trim((string) ($user->role?->role_name ?? '')));

        if (!in_array($actual, $requested, true)) {
            abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
        }

        return $next($request);
    }
}
