<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Core\Security\Rules\TenantExists;
use App\Http\Controllers\Controller;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\CRM\Models\CrmDeal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrmKanbanController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $stages = TenantStatus::forModule('sales_kanban')
            ->ordered()
            ->get();

        $deals = CrmDeal::with('partner')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'stages' => $stages,
                'deals' => $deals,
            ]);
        }

        return Inertia::render('CRM/Kanban/Index', [
            'stages' => $stages,
            'deals' => $deals,
        ]);
    }

    public function updateStage(Request $request, CrmDeal $deal): JsonResponse
    {
        $validated = $request->validate([
            'status_id' => ['required', 'uuid', new TenantExists('tenant_statuses', 'id')],
        ]);

        $deal->update([
            'status_id' => $validated['status_id'],
        ]);

        return response()->json([
            'message' => 'Estágio atualizado com sucesso.',
            'deal' => $deal->load(['partner', 'status']),
        ]);
    }
}
