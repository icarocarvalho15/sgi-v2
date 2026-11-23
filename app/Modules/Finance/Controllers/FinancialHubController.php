<?php

declare(strict_types=1);

namespace App\Modules\Finance\Controllers;

use App\Core\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\BankAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Services\CashFlowReportService;
use App\Modules\Finance\Services\DreReportService;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\KardexMovement;
use App\Modules\Inventory\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialHubController extends Controller
{
    public function index(
        Request $request,
        CashFlowReportService $cashFlowService,
        DreReportService $dreService
    ): Response {
        $tenant = TenantContext::get();
        if (! $tenant) {
            abort(404, 'Empresa não identificada.');
        }

        $activeTab = $request->input('tab', 'entries');

        // ---------------------------------------------------------------------
        // 1. TÍTULOS FINANCEIROS (Contas a Pagar & Receber)
        // ---------------------------------------------------------------------
        $entryType = $request->input('type');
        $entryStatus = $request->input('status');
        $entrySearch = $request->input('search');

        $entriesQuery = FinancialEntry::where('tenant_id', $tenant->id)
            ->with(['partner', 'chartOfAccount', 'costCenter', 'bankAccount'])
            ->orderBy('due_date', 'asc');

        if ($entryType) {
            $entriesQuery->where('type', $entryType);
        }
        if ($entryStatus) {
            $entriesQuery->where('status', $entryStatus);
        }
        if ($entrySearch) {
            $entriesQuery->where(function ($q) use ($entrySearch) {
                $q->where('description', 'ilike', "%{$entrySearch}%")
                    ->orWhere('entry_number', 'ilike', "%{$entrySearch}%");
            });
        }

        $entries = $entriesQuery->paginate(15, ['*'], 'entries_page')->withQueryString();

        $totalReceivable = (string) FinancialEntry::where('tenant_id', $tenant->id)
            ->where('type', 'receivable')
            ->whereIn('status', ['pending', 'partial'])
            ->sum('balance_due');

        $totalPayable = (string) FinancialEntry::where('tenant_id', $tenant->id)
            ->where('type', 'payable')
            ->whereIn('status', ['pending', 'partial'])
            ->sum('balance_due');

        $bankAccounts = BankAccount::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // ---------------------------------------------------------------------
        // 2. FLUXO DE CAIXA & DRE GERENCIAL
        // ---------------------------------------------------------------------
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $cashFlow = $cashFlowService->generate($startDate, $endDate);
        $dre = $dreService->generate($startDate, $endDate);

        // ---------------------------------------------------------------------
        // 3. RAZÃO KARDEX CONTÁBIL (Valoração de Insumos)
        // ---------------------------------------------------------------------
        $itemId = $request->input('item_id');
        $warehouseId = $request->input('warehouse_id');
        $movementType = $request->input('movement_type');

        $kardexQuery = KardexMovement::where('tenant_id', $tenant->id)
            ->with(['item', 'warehouse'])
            ->orderBy('created_at', 'desc');

        if ($itemId) {
            $kardexQuery->where('item_id', $itemId);
        }
        if ($warehouseId) {
            $kardexQuery->where('warehouse_id', $warehouseId);
        }
        if ($movementType) {
            $kardexQuery->where('movement_type', $movementType);
        }

        $movements = $kardexQuery->paginate(15, ['*'], 'kardex_page')->withQueryString();

        $items = Item::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'unit_of_measure']);

        $warehouses = Warehouse::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $selectedItem = $itemId ? Item::where('tenant_id', $tenant->id)->find($itemId) : null;

        return Inertia::render('Tenant/Finance/Hub', [
            'activeTab' => $activeTab,
            'entries' => $entries,
            'bankAccounts' => $bankAccounts,
            'metrics' => [
                'total_receivable' => $totalReceivable,
                'total_payable' => $totalPayable,
            ],
            'entryFilters' => [
                'type' => $entryType,
                'status' => $entryStatus,
                'search' => $entrySearch,
            ],
            'cashFlow' => $cashFlow,
            'dre' => $dre,
            'reportFilters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'movements' => $movements,
            'items' => $items,
            'warehouses' => $warehouses,
            'selectedItem' => $selectedItem,
            'kardexFilters' => [
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'movement_type' => $movementType,
            ],
        ]);
    }
}
