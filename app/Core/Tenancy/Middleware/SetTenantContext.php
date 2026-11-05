<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Middleware;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     * Sets up the tenant context in the PHP application, Spatie teams, and PostgreSQL RLS session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // SuperAdmin do Landlord SaaS: Opera em nível global (sem tenant vinculado).
        // Acesso a módulos de tenant somente é permitido via Personificação Técnica auditada.
        if ($user !== null && $user->is_super_admin && ! \App\Modules\CentralAdmin\Services\ImpersonationService::isImpersonating()) {
            TenantContext::clear();

            if (! $request->is('admin') && ! $request->is('admin/*') && ! $request->is('logout') && ! $request->is('api/*')) {
                return redirect()->route('admin.tenants.index')->with(
                    'info',
                    'O painel Landlord opera em nível global para gestão de empresas e assinaturas. Para acessar os módulos operacionais de uma gráfica, inicie uma sessão de personificação técnica auditada.'
                );
            }

            return $next($request);
        }

        $tenant = $this->resolveTenant($request);

        if ($tenant !== null) {
            TenantContext::set($tenant);

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($tenant->id);
            }
        }

        try {
            $response = $next($request);
        } finally {
            // Note: In long-lived octane/workers, cleanup happens here.
            // In standard HTTP requests, terminate() also cleans up.
        }

        return $response;
    }

    /**
     * Perform any final actions for the request lifecycle.
     */
    public function terminate(Request $request, Response $response): void
    {
        TenantContext::clear();
    }

    /**
     * Resolves the Tenant from authenticated user, header, or subdomain.
     */
    protected function resolveTenant(Request $request): ?Tenant
    {
        $user = $request->user();

        // 1. Personificação ativa para suporte técnico auditado (prioridade máxima)
        if (\App\Modules\CentralAdmin\Services\ImpersonationService::isImpersonating()) {
            $impersonated = \App\Modules\CentralAdmin\Services\ImpersonationService::targetTenant();
            if ($impersonated !== null) {
                return $impersonated;
            }
        }

        // Se for SuperAdmin e NÃO estiver personificando, nunca resolve tenant
        if ($user !== null && $user->is_super_admin) {
            return null;
        }

        // 2. Session tenant_id (permite alternar tenant no painel)
        if ($request->hasSession() && $request->session()->has('current_tenant_id')) {
            $sessionTenantId = $request->session()->get('current_tenant_id');
            $tenant = Tenant::find($sessionTenantId);
            if ($tenant) {
                return $tenant;
            }
        }

        // 3. Query param ?tenant_id=...
        $queryTenantId = $request->query('tenant_id');
        if (! empty($queryTenantId)) {
            $tenant = Tenant::find($queryTenantId);
            if ($tenant) {
                return $tenant;
            }
        }

        // 4. Authenticated user tenant_id
        if ($user !== null && ! empty($user->tenant_id)) {
            return Tenant::find($user->tenant_id);
        }

        // 5. Explicit header (e.g., API integration or tests)
        $headerTenantId = $request->header('X-Tenant-ID');
        if (! empty($headerTenantId)) {
            return Tenant::find($headerTenantId);
        }

        // 6. Subdomain resolution
        $host = $request->getHost();
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            $subdomain = $parts[0];
            $tenant = Tenant::where('subdomain', $subdomain)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        // 7. Fallback para desenvolvimento local: apenas para usuários comuns não-superadmin
        if (app()->environment('local') && ($user === null || ! $user->is_super_admin)) {
            return Tenant::where('name', 'like', '%Express%')->first() ?? Tenant::first();
        }

        return null;
    }
}
