<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Http\Requests\StoreWarehouseRequest;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WarehouseController extends Controller
{
    public function index(Request $request): Response
    {
        $warehouses = Warehouse::withCount('balances')
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('Inventory/Warehouses/Index', [
            'warehouses' => $warehouses,
        ]);
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        Warehouse::create($request->validated());

        return redirect()->route('tenant.inventory.warehouses.index')
            ->with('success', 'Almoxarifado cadastrado com sucesso!');
    }
}
