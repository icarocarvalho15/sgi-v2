<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Middleware;

use App\Core\Tenancy\Exceptions\TenantContextMissingException;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetTenantContext
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    /**
     * Resolve e configura o TenantContext para a requisição atual.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, bool $required = true): Response
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant instanceof Tenant) {
            $this->tenantContext->set($tenant);
        } elseif ($required && ! $this->tenantContext->isLandlordBypass()) {
            throw new TenantContextMissingException('Nenhum tenant foi identificado para esta requisição.');
        }

        try {
            return $next($request);
        } finally {
            // Em testes ou jobs, previne contaminação entre requisições
            if (app()->runningUnitTests()) {
                $this->tenantContext->clear();
            }
        }
    }

    /**
     * Estratégia em cascata para identificar o Tenant da requisição:
     * 1. Header 'X-Tenant-ID' (API, Mobile e Testes)
     * 2. Parâmetro de rota {tenant} ou {tenant_id}
     * 3. Sessão HTTP 'active_tenant_id'
     * 4. Usuário autenticado (primeiro tenant associado)
     * 5. Subdomínio da requisição
     */
    private function resolveTenant(Request $request): ?Tenant
    {
        // 1. Header explícito
        $headerId = $request->header('X-Tenant-ID');
        if (is_string($headerId) && $headerId !== '') {
            return Tenant::query()->find($headerId);
        }

        // 2. Parâmetro de rota
        $routeTenant = $request->route('tenant');
        if ($routeTenant instanceof Tenant) {
            return $routeTenant;
        }

        if (is_string($routeTenant) && $routeTenant !== '') {
            return Tenant::query()->where('id', $routeTenant)->orWhere('subdomain', $routeTenant)->first();
        }

        // 3. Sessão ativa
        if ($request->hasSession()) {
            $sessionTenantId = $request->session()->get('active_tenant_id') ?? $request->session()->get('current_tenant_id');
            if (is_string($sessionTenantId) && $sessionTenantId !== '') {
                $tenant = Tenant::query()->find($sessionTenantId);
                if ($tenant instanceof Tenant) {
                    return $tenant;
                }
            }
        }

        // 4. Usuário autenticado
        $user = $request->user();
        if ($user !== null && method_exists($user, 'tenants')) {
            /** @var Tenant|null $firstTenant */
            $firstTenant = $user->tenants()->first();
            if ($firstTenant instanceof Tenant) {
                return $firstTenant;
            }
        }

        // 5. Subdomínio
        $host = $request->getHost();
        $baseHost = parse_url((string) config('app.url', 'localhost'), PHP_URL_HOST);
        if (is_string($baseHost) && $baseHost !== '' && str_ends_with($host, $baseHost) && $host !== $baseHost) {
            $subdomain = rtrim(str_replace($baseHost, '', $host), '.');
            if ($subdomain !== '' && $subdomain !== 'www') {
                return Tenant::query()->where('subdomain', $subdomain)->first();
            }
        }

        return null;
    }
}
