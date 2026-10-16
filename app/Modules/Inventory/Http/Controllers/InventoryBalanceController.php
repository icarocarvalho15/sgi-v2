<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InventoryBalanceController extends Controller
{
    public function index(Request $request): Response
    {
        $warehouseId = $request->string('warehouse_id')->toString();
        $search = $request->string('search')->toString();
        $lowStock = $request->boolean('low_stock');

        $query = InventoryBalance::with(['item.unitOfMeasure', 'warehouse'])
            ->orderBy('warehouse_id');

        if ($warehouseId !== '') {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($search !== '') {
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($lowStock) {
            $query->whereHas('item', function ($q) {
                $q->whereColumn('inventory_balances.quantity_on_hand', '<=', 'items.min_stock')
                    ->where('items.min_stock', '>', 0);
            });
        }

        $balances = $query->paginate(25)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Inventory/Balances/Index', [
            'balances' => $balances,
            'warehouses' => $warehouses,
            'filters' => [
                'warehouse_id' => $warehouseId,
                'search' => $search,
                'low_stock' => $lowStock,
            ],
        ]);
    }
}
