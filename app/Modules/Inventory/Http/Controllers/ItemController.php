<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Http\Requests\StoreItemRequest;
use App\Modules\Inventory\Http\Requests\UpdateItemRequest;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\ItemCategory;
use App\Modules\Inventory\Models\UnitOfMeasure;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ItemController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString();
        $categoryId = $request->string('category_id')->toString();

        $query = Item::with(['unitOfMeasure', 'category', 'defaultWarehouse', 'balances'])
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($type === 'raw_material') {
            $query->where('is_raw_material', true);
        } elseif ($type === 'manufactured') {
            $query->where('is_manufactured', true);
        } elseif ($type === 'sellable') {
            $query->where('is_sellable', true);
        } elseif ($type === 'service') {
            $query->where('is_service', true);
        }

        if ($categoryId !== '') {
            $query->where('category_id', $categoryId);
        }

        $items = $query->paginate(20)->withQueryString();

        $units = UnitOfMeasure::where('is_active', true)->orderBy('code')->get();
        $categories = ItemCategory::orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Inventory/Items/Index', [
            'items' => $items,
            'units' => $units,
            'categories' => $categories,
            'warehouses' => $warehouses,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'category_id' => $categoryId,
            ],
        ]);
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        Item::create($request->validated());

        return redirect()->route('tenant.inventory.items.index')
            ->with('success', 'Item cadastrado com sucesso!');
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $item->update($request->validated());

        return redirect()->route('tenant.inventory.items.index')
            ->with('success', 'Item atualizado com sucesso!');
    }
}
