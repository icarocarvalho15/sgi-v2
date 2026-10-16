<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\Http\Controllers;

use App\Core\Tenancy\Models\ImpersonationLog;
use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController
{
    public function __invoke(Request $request): Response
    {
        $totalTenants = Tenant::query()->count();
        $activeTenants = Tenant::query()->where('status', 'active')->count();
        $suspendedTenants = Tenant::query()->where('status', 'suspended')->count();
        $trialTenants = Tenant::query()->where('status', 'trial')->count();
        $totalUsers = User::query()->where('is_super_admin', false)->count();

        // Cálculo aproximado de MRR dos planos ativos
        $estimatedMrr = (float) Tenant::query()
            ->join('plans', 'tenants.plan_id', '=', 'plans.id')
            ->where('tenants.status', 'active')
            ->sum('plans.price');

        $recentTenants = Tenant::query()
            ->with(['plan:id,name,price', 'users' => function ($q) {
                $q->wherePivot('is_owner', true);
            }])
            ->latest('created_at')
            ->take(5)
            ->get();

        $recentImpersonations = ImpersonationLog::query()
            ->with(['impersonator:id,name,email', 'tenant:id,name,subdomain'])
            ->latest('started_at')
            ->take(5)
            ->get();

        return Inertia::render('CentralAdmin/Dashboard', [
            'metrics' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'suspended_tenants' => $suspendedTenants,
                'trial_tenants' => $trialTenants,
                'total_users' => $totalUsers,
                'estimated_mrr' => $estimatedMrr,
            ],
            'recentTenants' => $recentTenants,
            'recentImpersonations' => $recentImpersonations,
        ]);
    }
}
