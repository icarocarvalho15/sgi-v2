<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Core\Security\Rules\TenantExists;
use App\Http\Controllers\Controller;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\KardexLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InventoryAdjustmentController extends Controller
{
    public function create(): Response
    {
        $items = Item::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'unit_of_measure', 'standard_cost']);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('Tenant/Inventory/Adjustments/Create', [
            'items' => $items,
            'warehouses' => $warehouses,
        ]);
    }

    public function store(Request $request, KardexLedgerService $kardexService): RedirectResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'uuid', new TenantExists('items')],
            'warehouse_id' => ['required', 'uuid', new TenantExists('warehouses')],
            'direction' => ['required', 'in:in,out'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['required', 'string', 'max:500'],
        ]);

        try {
            $kardexService->recordMovement([
                'item_id' => $validated['item_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'movement_type' => 'inventory_adjustment',
                'direction' => $validated['direction'],
                'quantity' => (string) $validated['quantity'],
                'unit_cost' => isset($validated['unit_cost']) ? (string) $validated['unit_cost'] : '0.0000',
                'user_id' => Auth::id(),
                'notes' => $validated['notes'],
            ]);

            return redirect()->route('tenant.inventory.balances.index')
                ->with('success', 'Ajuste de estoque registrado com sucesso no Kardex!');
        } catch (InsufficientStockException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }
    }
}
