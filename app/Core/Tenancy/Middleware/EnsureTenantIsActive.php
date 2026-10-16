<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Middleware;

use App\Core\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantIsActive
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    /**
     * Garante que o tenant ativo está com status operacional válido.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantContext->get();

        if ($tenant === null) {
            abort(Response::HTTP_UNAUTHORIZED, 'Nenhum tenant ativo associado à sessão.');
        }

        if (! $tenant->isActive()) {
            abort(Response::HTTP_FORBIDDEN, 'Acesso bloqueado: a assinatura da sua empresa está suspensa ou inativa.');
        }

        if ($tenant->status === 'trial' && $tenant->trial_ends_at !== null && $tenant->trial_ends_at->isPast()) {
            abort(Response::HTTP_FORBIDDEN, 'Período de avaliação expirado. Faça o upgrade do seu plano para continuar.');
        }

        return $next($request);
    }
}
