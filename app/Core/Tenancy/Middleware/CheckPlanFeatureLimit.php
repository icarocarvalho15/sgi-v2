<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Middleware;

use App\Core\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CheckPlanFeatureLimit
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    /**
     * Valida se a feature ou limite solicitado está habilitado no plano ativo do Tenant.
     * Exemplo de uso nas rotas: ->middleware('plan.feature:nfe_enabled') ou ->middleware('plan.limit:max_users')
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $tenant = $this->tenantContext->get();

        if ($tenant === null) {
            abort(Response::HTTP_UNAUTHORIZED, 'Tenant não identificado.');
        }

        $plan = $tenant->plan;

        if ($plan === null) {
            abort(Response::HTTP_FORBIDDEN, 'Nenhum plano associado à empresa.');
        }

        if (! $plan->hasFeature($featureKey)) {
            abort(Response::HTTP_FORBIDDEN, "O recurso '{$featureKey}' não está disponível no plano contratado ({$plan->name}). Faça o upgrade para utilizá-lo.");
        }

        return $next($request);
    }
}
