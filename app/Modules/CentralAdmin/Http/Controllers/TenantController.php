<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\Http\Controllers;

use App\Core\Tenancy\Models\Plan;
use App\Core\Tenancy\Models\Tenant;
use App\Modules\CentralAdmin\Actions\ProvisionTenantAction;
use App\Modules\CentralAdmin\Actions\UpdateTenantStatusAction;
use App\Modules\CentralAdmin\DTOs\ProvisionTenantDTO;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class TenantController
{
    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Tenant::query()
            ->with(['plan:id,name,price,slug', 'subscriptions' => fn ($q) => $q->latest('created_at')])
            ->withCount('users');

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        if (is_string($search) && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subdomain', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        $tenants = $query->latest('created_at')->paginate(15)->withQueryString();
        $plans = Plan::query()->where('is_active', true)->get(['id', 'name', 'price']);

        return Inertia::render('CentralAdmin/Tenants/Index', [
            'tenants' => $tenants,
            'plans' => $plans,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(Request $request, ProvisionTenantAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:30'],
            'subdomain' => ['required', 'string', 'alpha_dash', 'max:50', 'unique:tenants,subdomain'],
            'plan_id' => ['required', 'uuid', 'exists:plans,id'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],
        ]);

        $dto = ProvisionTenantDTO::fromArray($validated);
        $action->execute($dto);

        return redirect()->back()->with('success', "Empresa '{$dto->name}' provisionada com sucesso!");
    }

    public function updateStatus(
        Request $request,
        string $tenantId,
        UpdateTenantStatusAction $action
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,trial,suspended,canceled'],
        ]);

        $tenant = $action->execute($tenantId, (string) $validated['status']);

        return redirect()->back()->with('success', "Status da empresa '{$tenant->name}' atualizado para {$tenant->status}.");
    }
}
