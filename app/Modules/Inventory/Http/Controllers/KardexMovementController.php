<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\KardexMovement;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class KardexMovementController extends Controller
{
    public function index(Request $request): Response
    {
        $itemId = $request->string('item_id')->toString();
        $warehouseId = $request->string('warehouse_id')->toString();
        $movementType = $request->string('movement_type')->toString();

        $query = KardexMovement::with(['item.unitOfMeasure', 'warehouse', 'user'])
            ->orderByDesc('created_at');

        if ($itemId !== '') {
            $query->where('item_id', $itemId);
        }

        if ($warehouseId !== '') {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($movementType !== '') {
            $query->where('movement_type', $movementType);
        }

        $movements = $query->paginate(25)->withQueryString();
        $items = Item::where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name']);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        return Inertia::render('Inventory/Kardex/Index', [
            'movements' => $movements,
            'items' => $items,
            'warehouses' => $warehouses,
            'filters' => [
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'movement_type' => $movementType,
            ],
        ]);
    }
}
