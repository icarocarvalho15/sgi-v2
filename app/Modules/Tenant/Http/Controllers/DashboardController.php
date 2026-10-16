<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Http\Controllers;

use App\Core\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController
{
    public function __invoke(Request $request, TenantContext $context): Response
    {
        $tenant = $context->get();
        /** @var User $user */
        $user = $request->user();

        $plan = $tenant?->plan;
        $activeMembersCount = $tenant?->users()->count() ?? 0;

        return Inertia::render('Tenant/Dashboard', [
            'tenant' => [
                'id' => $tenant?->id,
                'name' => $tenant?->name,
                'trade_name' => $tenant?->trade_name,
                'subdomain' => $tenant?->subdomain,
                'status' => $tenant?->status,
                'active_members_count' => $activeMembersCount,
            ],
            'plan' => [
                'name' => $plan?->name ?? 'Sem Plano',
                'slug' => $plan?->slug ?? 'none',
                'max_users' => $plan?->max_users ?? 0,
                'features' => $plan?->features ?? [],
            ],
            'userRole' => $user->roles()->where('tenant_id', $tenant?->id)->value('name') ?? 'Colaborador',
        ]);
    }
}
