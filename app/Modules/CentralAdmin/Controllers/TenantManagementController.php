<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\Controllers;

use App\Core\Tenancy\Models\Plan;
use App\Core\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Modules\CentralAdmin\Models\ImpersonationLog;
use App\Modules\CentralAdmin\Services\ImpersonationService;
use App\Modules\CoreAdmin\Services\DefaultStatusesSeederService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TenantManagementController extends Controller
{
    public function index(): Response
    {
        $tenants = Tenant::with(['plan'])
            ->withCount('users')
            ->orderBy('created_at', 'desc')
            ->get();

        $plans = Plan::where('is_active', true)->get();

        $recentLogs = ImpersonationLog::with(['user', 'tenant'])
            ->orderBy('started_at', 'desc')
            ->limit(10)
            ->get();

        $totalActive = $tenants->where('status', 'active')->count();
        $totalSuspended = $tenants->where('status', 'suspended')->count();
        $totalUsers = $tenants->sum('users_count');
        $mrr = $tenants->where('status', 'active')->sum(fn ($t) => (float) ($t->plan?->price ?? 0));

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'plans' => $plans,
            'recentLogs' => $recentLogs,
            'stats' => [
                'totalTenants' => $tenants->count(),
                'activeTenants' => $totalActive,
                'suspendedTenants' => $totalSuspended,
                'totalUsers' => $totalUsers,
                'mrr' => $mrr,
            ],
            'isImpersonating' => ImpersonationService::isImpersonating(),
            'impersonatedTenant' => ImpersonationService::targetTenant(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:tenants,subdomain'],
            'plan_id' => ['nullable', 'uuid', 'exists:plans,id'],
        ]);

        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => $validated['name'],
            'subdomain' => strtolower($validated['subdomain']),
            'plan_id' => $validated['plan_id'] ?? null,
            'status' => 'active',
        ]);

        DefaultStatusesSeederService::seedForTenant($tenant->id);

        return redirect()->route('admin.tenants.index')
            ->with('success', "Tenant \"{$tenant->name}\" criado com sucesso e status operacionais inicializados!");
    }

    public function updateSubscription(Tenant $tenant, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['nullable', 'uuid', 'exists:plans,id'],
            'status' => ['required', 'string', 'in:active,suspended,cancelled'],
        ]);

        $tenant->update([
            'plan_id' => $validated['plan_id'] ?? $tenant->plan_id,
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.tenants.index')
            ->with('success', "Assinatura e status da empresa \"{$tenant->name}\" atualizados com sucesso!");
    }

    public function metrics(): Response
    {
        $tenants = Tenant::with('plan')->withCount('users')->get();
        $plans = Plan::withCount('tenants')->get();

        $activeTenants = $tenants->where('status', 'active');
        $mrr = $activeTenants->sum(fn ($t) => (float) ($t->plan?->price ?? 0));
        $arr = $mrr * 12;

        $planDistribution = $plans->map(function ($plan) {
            return [
                'name' => $plan->name,
                'count' => $plan->tenants_count,
                'revenue' => (float) $plan->price * $plan->tenants_count,
            ];
        });

        return Inertia::render('Admin/Metrics/Index', [
            'metrics' => [
                'mrr' => $mrr,
                'arr' => $arr,
                'totalTenants' => $tenants->count(),
                'activeTenants' => $activeTenants->count(),
                'suspendedTenants' => $tenants->where('status', 'suspended')->count(),
                'totalUsers' => $tenants->sum('users_count'),
                'planDistribution' => $planDistribution,
            ],
        ]);
    }

    public function auditLogs(): Response
    {
        $logs = ImpersonationLog::with(['user', 'tenant'])
            ->orderBy('started_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
        ]);
    }

    public function impersonate(Tenant $tenant, Request $request): RedirectResponse
    {
        $reason = $request->input('reason', 'Atendimento de Suporte Técnico ao Cliente');
        $ip = $request->ip() ?: '127.0.0.1';

        ImpersonationService::start(Auth::user(), $tenant, $reason, $ip);

        $request->session()->put('current_tenant_id', $tenant->id);

        return redirect()->route('crm.partners.index')
            ->with('success', "Você agora está personificando a empresa [{$tenant->name}]. Todas as ações são auditadas.");
    }

    public function stopImpersonation(Request $request): RedirectResponse
    {
        ImpersonationService::stop();

        $request->session()->forget('current_tenant_id');

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Sessão de personificação encerrada. Você retornou ao Painel Landlord.');
    }
}
