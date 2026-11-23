<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->is_active === false) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Seu usuário foi desativado pelo administrador da empresa.',
            ]);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (empty($roles)) {
            return $next($request);
        }

        if (! $user->hasRole($roles)) {
            abort(403, "Acesso restrito: o seu perfil ({$user->role_name}) não possui permissão para acessar este módulo da empresa.");
        }

        return $next($request);
    }
}
