<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\ItemCategory;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryBalanceController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $warehouseId = $request->input('warehouse_id');
        $categoryId = $request->input('category_id');
        $search = $request->input('search');

        $query = InventoryBalance::with(['item.category', 'warehouse'])
            ->join('items', 'inventory_balances.item_id', '=', 'items.id')
            ->select('inventory_balances.*');

        if ($warehouseId) {
            $query->where('inventory_balances.warehouse_id', $warehouseId);
        }

        if ($categoryId) {
            $query->where('items.category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('items.name', 'ilike', "%{$search}%")
                    ->orWhere('items.code', 'ilike', "%{$search}%");
            });
        }

        $balances = $query->orderBy('items.name')->paginate(15)->withQueryString();

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $categories = ItemCategory::orderBy('name')->get();

        // Métricas resumidas
        $totalStockValue = InventoryBalance::sum('total_value');
        $totalItemsCount = InventoryBalance::where('quantity_on_hand', '>', 0)->count();

        if ($request->wantsJson()) {
            return response()->json($balances);
        }

        return Inertia::render('Tenant/Inventory/Balances/Index', [
            'balances' => $balances,
            'warehouses' => $warehouses,
            'categories' => $categories,
            'filters' => [
                'warehouse_id' => $warehouseId,
                'category_id' => $categoryId,
                'search' => $search,
            ],
            'metrics' => [
                'total_stock_value' => $totalStockValue,
                'total_items_count' => $totalItemsCount,
            ],
        ]);
    }
}
