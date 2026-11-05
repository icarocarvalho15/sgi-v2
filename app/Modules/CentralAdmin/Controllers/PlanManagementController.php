<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\Controllers;

use App\Core\Tenancy\Models\Plan;
use App\Core\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlanManagementController extends Controller
{
    public function index(): Response
    {
        $plans = Plan::withCount(['tenants' => function ($q) {
            $q->where('status', 'active');
        }])
            ->orderBy('price')
            ->get();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_interval' => ['required', 'string', 'in:monthly,yearly'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_monthly_orders' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:500'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['name']);
        // Garante slug único
        $count = Plan::where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        Plan::create([
            'id' => (string) Str::uuid(),
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'billing_interval' => $validated['billing_interval'],
            'max_users' => $validated['max_users'],
            'max_monthly_orders' => $validated['max_monthly_orders'],
            'features' => $validated['features'] ?? [],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', "Plano SaaS \"{$validated['name']}\" criado com sucesso!");
    }

    public function update(Plan $plan, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_interval' => ['required', 'string', 'in:monthly,yearly'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_monthly_orders' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:500'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $plan->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'billing_interval' => $validated['billing_interval'],
            'max_users' => $validated['max_users'],
            'max_monthly_orders' => $validated['max_monthly_orders'],
            'features' => $validated['features'] ?? [],
            'is_active' => $validated['is_active'] ?? $plan->is_active,
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', "Plano \"{$plan->name}\" atualizado com sucesso!");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->tenants()->where('status', 'active')->count() > 0) {
            return redirect()->route('admin.plans.index')
                ->with('error', "Não é possível excluir um plano com empresas assinantes ativas vinculadas.");
        }

        $name = $plan->name;
        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', "Plano \"{$name}\" removido!");
    }
}
