<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\Http\Controllers;

use App\Core\Tenancy\Models\Plan;
use App\Modules\CentralAdmin\Actions\SavePlanAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PlanController
{
    public function index(): Response
    {
        $plans = Plan::query()
            ->withCount(['tenants as active_subscribers_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderBy('price')
            ->get();

        return Inertia::render('CentralAdmin/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function store(Request $request, SavePlanAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:plans,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_interval' => ['required', 'string', 'in:monthly,yearly,quarterly'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_storage_mb' => ['required', 'integer', 'min:100'],
            'is_active' => ['boolean'],
            'features' => ['nullable', 'array'],
        ]);

        $plan = $action->execute($validated);

        return redirect()->back()->with('success', "Plano '{$plan->name}' cadastrado com sucesso!");
    }

    public function update(Request $request, string $planId, SavePlanAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', "unique:plans,slug,{$planId},id"],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_interval' => ['required', 'string', 'in:monthly,yearly,quarterly'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_storage_mb' => ['required', 'integer', 'min:100'],
            'is_active' => ['boolean'],
            'features' => ['nullable', 'array'],
        ]);

        $plan = $action->execute($validated, $planId);

        return redirect()->back()->with('success', "Plano '{$plan->name}' atualizado com sucesso!");
    }
}
