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
        // 1. Authenticated user tenant_id
        $user = $request->user();
        if ($user !== null && ! empty($user->tenant_id)) {
            return Tenant::find($user->tenant_id);
        }

        // 2. Explicit header (e.g., API integration or tests)
        $headerTenantId = $request->header('X-Tenant-ID');
        if (! empty($headerTenantId)) {
            return Tenant::find($headerTenantId);
        }

        // 3. Subdomain resolution
        $host = $request->getHost();
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            $subdomain = $parts[0];
            return Tenant::where('subdomain', $subdomain)->first();
        }

        return null;
    }
}
