<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Production\Enums\ProductionOrderStatus;
use App\Modules\Production\Models\ProductionOrderOperation;
use App\Modules\Production\Models\WorkCenter;
use App\Modules\Production\Services\ProductionOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShopFloorController extends Controller
{
    public function __construct(
        private readonly ProductionOrderService $productionOrderService,
    ) {}

    public function index(Request $request): Response
    {
        $workCenterId = $request->string('work_center_id')->toString();
        $status = $request->string('status')->toString();
        $barcode = $request->string('barcode')->toString();

        $query = ProductionOrderOperation::with([
            'productionOrder.item.unitOfMeasure',
            'workCenter',
            'operator',
        ])->whereHas('productionOrder', function ($q) {
            $q->whereIn('status', [
                ProductionOrderStatus::Released,
                ProductionOrderStatus::InProgress,
            ]);
        })->orderBy('sequence');

        if ($workCenterId !== '') {
            $query->where('work_center_id', $workCenterId);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($barcode !== '') {
            $query->whereHas('productionOrder', function ($q) use ($barcode) {
                $q->where('order_number', 'like', "%{$barcode}%")
                    ->orWhereHas('item', function ($iq) use ($barcode) {
                        $iq->where('sku', 'like', "%{$barcode}%");
                    });
            });
        }

        $operations = $query->paginate(15)->withQueryString();
        $workCenters = WorkCenter::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Production/ShopFloor/Index', [
            'operations' => $operations,
            'workCenters' => $workCenters,
            'filters' => [
                'work_center_id' => $workCenterId,
                'status' => $status,
                'barcode' => $barcode,
            ],
        ]);
    }

    public function start(ProductionOrderOperation $operation): RedirectResponse
    {
        $this->productionOrderService->startOperation($operation, auth()->id());

        return back()->with('success', 'Operação iniciada!');
    }

    public function pause(ProductionOrderOperation $operation): RedirectResponse
    {
        $this->productionOrderService->pauseOperation($operation);

        return back()->with('success', 'Operação pausada!');
    }

    public function complete(ProductionOrderOperation $operation): RedirectResponse
    {
        $this->productionOrderService->completeOperation($operation);

        return back()->with('success', 'Operação concluída com sucesso!');
    }
}
