<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSuperAdmin
{
    /**
     * Garante que apenas usuários com a flag is_super_admin = true acessem a área Landlord.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            if ($request->expectsJson()) {
                abort(Response::HTTP_UNAUTHORIZED, 'Não autenticado.');
            }

            return redirect()->guest(route('login'));
        }

        if (! $user->is_super_admin) {
            abort(Response::HTTP_FORBIDDEN, 'Acesso negado: área exclusiva para a administração global do SaaS (Landlord).');
        }

        return $next($request);
    }
}
