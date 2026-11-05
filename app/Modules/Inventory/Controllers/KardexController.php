<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\KardexMovement;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KardexController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $itemId = $request->input('item_id');
        $warehouseId = $request->input('warehouse_id');
        $movementType = $request->input('movement_type');

        $query = KardexMovement::with(['item', 'warehouse'])
            ->orderBy('created_at', 'desc');

        if ($itemId) {
            $query->where('item_id', $itemId);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($movementType) {
            $query->where('movement_type', $movementType);
        }

        $movements = $query->paginate(20)->withQueryString();

        $items = Item::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'unit_of_measure']);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        $selectedItem = $itemId ? Item::find($itemId) : null;

        if ($request->wantsJson()) {
            return response()->json($movements);
        }

        return Inertia::render('Tenant/Inventory/Kardex/Index', [
            'movements' => $movements,
            'items' => $items,
            'warehouses' => $warehouses,
            'selectedItem' => $selectedItem,
            'filters' => [
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'movement_type' => $movementType,
            ],
        ]);
    }
}
