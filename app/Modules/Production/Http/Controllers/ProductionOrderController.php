<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Production\DTOs\CreateProductionOrderDTO;
use App\Modules\Production\DTOs\RecordProductionDTO;
use App\Modules\Production\Enums\ProductionOrderPriority;
use App\Modules\Production\Http\Requests\RecordProductionRequest;
use App\Modules\Production\Http\Requests\StoreProductionOrderRequest;
use App\Modules\Production\Models\Bom;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Production\Services\ProductionOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProductionOrderController extends Controller
{
    public function __construct(
        private readonly ProductionOrderService $productionOrderService,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $query = ProductionOrder::with([
            'item.unitOfMeasure',
            'bom',
            'warehouse',
            'materialsWarehouse',
            'operations.workCenter',
        ])->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($iq) use ($search) {
                        $iq->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $orders = $query->paginate(20)->withQueryString();

        $manufacturedItems = Item::where('is_manufactured', true)->orderBy('name')->get();
        $boms = Bom::where('is_active', true)->with('item')->orderBy('code')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Production/Orders/Index', [
            'orders' => $orders,
            'manufacturedItems' => $manufacturedItems,
            'boms' => $boms,
            'warehouses' => $warehouses,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function show(ProductionOrder $order): Response
    {
        $order->load([
            'item.unitOfMeasure',
            'bom',
            'warehouse',
            'materialsWarehouse',
            'materials.item.unitOfMeasure',
            'operations.workCenter',
            'operations.operator',
        ]);

        return Inertia::render('Production/Orders/Show', [
            'order' => $order,
        ]);
    }

    public function store(StoreProductionOrderRequest $request): RedirectResponse
    {
        $dto = new CreateProductionOrderDTO(
            itemId: $request->string('item_id')->toString(),
            bomId: $request->string('bom_id')->toString(),
            warehouseId: $request->string('warehouse_id')->toString(),
            materialsWarehouseId: $request->string('materials_warehouse_id')->toString(),
            plannedQuantity: (string) $request->input('planned_quantity'),
            priority: $request->enum('priority', ProductionOrderPriority::class) ?? ProductionOrderPriority::Medium,
            startDate: $request->input('start_date'),
            dueDate: $request->input('due_date'),
            notes: $request->input('notes'),
        );

        $order = $this->productionOrderService->create($dto);

        return redirect()->route('tenant.production.orders.index')
            ->with('success', "Ordem de Produção {$order->order_number} criada no estado Rascunho!");
    }

    public function release(ProductionOrder $order): RedirectResponse
    {
        $this->productionOrderService->release($order);

        return back()->with('success', "Ordem de Produção {$order->order_number} liberada para o chão de fábrica!");
    }

    public function recordProduction(RecordProductionRequest $request, ProductionOrder $order): RedirectResponse
    {
        $dto = new RecordProductionDTO(
            productionOrderId: $order->id,
            producedQuantity: (string) $request->input('produced_quantity'),
            scrappedQuantity: (string) ($request->input('scrapped_quantity') ?? '0.0000'),
            notes: $request->input('notes'),
            userId: auth()->id(),
        );

        $this->productionOrderService->recordProduction($dto);

        return back()->with('success', "Apontamento de produção registrado com sucesso na OP {$order->order_number}!");
    }
}
