<?php

declare(strict_types=1);

namespace App\Modules\CoreAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CoreAdmin\Models\TenantStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TenantStatusController extends Controller
{
    public function index(): Response
    {
        $statuses = TenantStatus::orderBy('module')
            ->orderBy('position')
            ->get();

        $byModule = [
            'crm_deals' => $statuses->where('module', 'crm_deals')->values(),
            'quotes' => $statuses->where('module', 'quotes')->values(),
            'production_orders' => $statuses->where('module', 'production_orders')->values(),
        ];

        return Inertia::render('Tenant/Settings/Statuses/Index', [
            'statusesByModule' => $byModule,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'module' => ['required', 'string', 'in:crm_deals,quotes,production_orders'],
            'name' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'is_final' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $maxPos = TenantStatus::where('module', $validated['module'])->max('position') ?? 0;

        TenantStatus::create([
            'module' => $validated['module'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'color' => $validated['color'],
            'position' => $maxPos + 1,
            'is_final' => $validated['is_final'] ?? false,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return redirect()->route('tenant.settings.statuses.index')
            ->with('success', "Status \"{$validated['name']}\" criado com sucesso!");
    }

    public function update(TenantStatus $status, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'is_final' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer'],
        ]);

        $status->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'color' => $validated['color'],
            'is_final' => $validated['is_final'] ?? $status->is_final,
            'is_default' => $validated['is_default'] ?? $status->is_default,
            'position' => $validated['position'] ?? $status->position,
        ]);

        return redirect()->route('tenant.settings.statuses.index')
            ->with('success', "Status \"{$status->name}\" atualizado!");
    }

    public function destroy(TenantStatus $status): RedirectResponse
    {
        $name = $status->name;
        $status->delete();

        return redirect()->route('tenant.settings.statuses.index')
            ->with('success', "Status \"{$name}\" removido!");
    }
}
