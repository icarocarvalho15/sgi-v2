<?php

declare(strict_types=1);

namespace App\Modules\Production\Controllers;

use App\Core\Security\Rules\TenantExists;
use App\Http\Controllers\Controller;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Production\Models\WorkCenter;
use App\Modules\Production\Services\ProductionOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductionOrderController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $orders = ProductionOrder::with(['status', 'salesOrder.customer', 'bom'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $statuses = TenantStatus::forModule('production_orders')
            ->ordered()
            ->get();

        if ($request->wantsJson()) {
            return response()->json($orders);
        }

        return Inertia::render('Tenant/Production/Orders/Index', [
            'orders' => $orders,
            'statuses' => $statuses,
        ]);
    }

    public function show(ProductionOrder $order): Response|JsonResponse
    {
        $order->load([
            'status',
            'salesOrder.customer',
            'bom.items',
            'materials',
            'timeLogs.workCenter',
            'timeLogs.user',
        ]);

        $availableStatuses = TenantStatus::forModule('production_orders')
            ->ordered()
            ->get();

        $workCenters = WorkCenter::where('is_active', true)->orderBy('name')->get();

        if (request()->wantsJson()) {
            return response()->json($order);
        }

        return Inertia::render('Tenant/Production/Orders/Show', [
            'order' => $order,
            'availableStatuses' => $availableStatuses,
            'workCenters' => $workCenters,
        ]);
    }

    public function updateStatus(Request $request, ProductionOrder $order): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'status_id' => ['required', 'uuid', new TenantExists('tenant_statuses', 'id')],
        ]);

        $updated = ProductionOrderService::updateStatus($order, $validated['status_id']);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Status da Ordem de Produção atualizado.',
                'order' => $updated->load('status'),
            ]);
        }

        return redirect()->route('production.orders.show', $order->id)
            ->with('success', 'Status da O.P. atualizado para: ' . $updated->status->name);
    }

    public function logTime(Request $request, ProductionOrder $order): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'work_center_id' => ['required', 'uuid', new TenantExists('work_centers', 'id')],
            'operation_name' => ['required', 'string', 'max:255'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date'],
            'good_quantity' => ['required', 'integer', 'min:0'],
            'scrap_quantity' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $log = ProductionOrderService::logOperation($order, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Apontamento de produção registrado com sucesso.',
                'log' => $log->load('workCenter'),
                'produced_quantity' => $order->fresh()->produced_quantity,
            ], 201);
        }

        return redirect()->route('production.orders.show', $order->id)
            ->with('success', 'Apontamento registrado com sucesso.');
    }
}
