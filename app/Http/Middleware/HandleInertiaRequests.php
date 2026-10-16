<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $tenant = function_exists('tenant') ? tenant() : null;
        $isImpersonating = (bool) $request->session()->get('is_impersonating', false);
        $impersonatedTenantName = $request->session()->get('impersonated_tenant_name');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_super_admin' => (bool) $user->is_super_admin,
                ] : null,
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'trade_name' => $tenant->trade_name,
                    'subdomain' => $tenant->subdomain,
                    'plan' => $tenant->plan ? [
                        'name' => $tenant->plan->name,
                        'slug' => $tenant->plan->slug,
                        'features' => $tenant->plan->features ?? [],
                    ] : null,
                ] : null,
                'is_impersonating' => $isImpersonating,
                'impersonated_tenant_name' => $impersonatedTenantName,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
