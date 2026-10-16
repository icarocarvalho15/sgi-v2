<?php

declare(strict_types=1);

namespace App\Modules\IdentityACL\Http\Controllers;

use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TenantSelectionController
{
    public function index(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tenants = $user->tenants()
            ->with('plan:id,name,slug')
            ->get();

        if ($tenants->isEmpty() && ! $user->is_super_admin) {
            abort(403, 'Você não possui acesso a nenhuma empresa cadastrada.');
        }

        return Inertia::render('IdentityACL/SelectTenant', [
            'tenants' => $tenants,
            'currentTenantId' => $request->session()->get('active_tenant_id'),
        ]);
    }

    public function select(Request $request, string $tenantId): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Valida se o usuário tem acesso ao tenant selecionado (ou se é super admin)
        $hasAccess = $user->is_super_admin || $user->tenants()->where('tenants.id', $tenantId)->exists();

        if (! $hasAccess) {
            abort(403, 'Acesso não autorizado para esta empresa.');
        }

        /** @var Tenant $tenant */
        $tenant = Tenant::query()->findOrFail($tenantId);

        if (! $tenant->isActive() && ! $user->is_super_admin) {
            return redirect()->back()->with('error', 'Esta empresa está com a assinatura suspensa ou inativa.');
        }

        $request->session()->put('active_tenant_id', $tenant->id);

        return redirect()->route('tenant.dashboard')->with('success', "Ambiente alternado para {$tenant->name}.");
    }
}
