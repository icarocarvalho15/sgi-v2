<?php

declare(strict_types=1);

namespace App\Modules\CoreAdmin\Controllers;

use App\Core\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Partner;
use App\Modules\Finance\Models\BankAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Item;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Quotes\Models\Quote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = TenantContext::get();
        if (! $tenant) {
            abort(404, 'Empresa não identificada.');
        }

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        // ---------------------------------------------------------------------
        // 1. MÉTRICAS FINANCEIRAS
        // ---------------------------------------------------------------------
        $totalReceivableMonth = (string) FinancialEntry::where('tenant_id', $tenant->id)
            ->where('type', 'receivable')
            ->whereBetween('due_date', [$startOfMonth, $endOfMonth])
            ->sum('original_amount');

        $receivedMonth = (string) FinancialEntry::where('tenant_id', $tenant->id)
            ->where('type', 'receivable')
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('paid_amount');

        $totalPayableMonth = (string) FinancialEntry::where('tenant_id', $tenant->id)
            ->where('type', 'payable')
            ->whereBetween('due_date', [$startOfMonth, $endOfMonth])
            ->sum('original_amount');

        $paidMonth = (string) FinancialEntry::where('tenant_id', $tenant->id)
            ->where('type', 'payable')
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('paid_amount');

        $pendingReceivable = (string) FinancialEntry::where('tenant_id', $tenant->id)
            ->where('type', 'receivable')
            ->whereIn('status', ['pending', 'partial'])
            ->sum('balance_due');

        $pendingPayable = (string) FinancialEntry::where('tenant_id', $tenant->id)
            ->where('type', 'payable')
            ->whereIn('status', ['pending', 'partial'])
            ->sum('balance_due');

        $bankAccounts = BankAccount::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get(['id', 'name', 'bank_code', 'current_balance']);

        $totalBankBalance = (string) $bankAccounts->sum('current_balance');

        // Histórico dos últimos 6 meses para gráfico comparativo
        $historyMonths = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $mStart = $monthDate->copy()->startOfMonth()->toDateString();
            $mEnd = $monthDate->copy()->endOfMonth()->toDateString();

            $inflow = FinancialEntry::where('tenant_id', $tenant->id)
                ->where('type', 'receivable')
                ->whereBetween('payment_date', [$mStart, $mEnd])
                ->sum('paid_amount');

            $outflow = FinancialEntry::where('tenant_id', $tenant->id)
                ->where('type', 'payable')
                ->whereBetween('payment_date', [$mStart, $mEnd])
                ->sum('paid_amount');

            $historyMonths[] = [
                'month' => $monthDate->translatedFormat('M/Y'),
                'inflow' => (float) $inflow,
                'outflow' => (float) $outflow,
                'net' => (float) ($inflow - $outflow),
            ];
        }

        // ---------------------------------------------------------------------
        // 2. MÉTRICAS COMERCIAIS & FUNIL DE ORÇAMENTOS
        // ---------------------------------------------------------------------
        $quotesQuery = Quote::where('tenant_id', $tenant->id);
        $totalQuotesMonth = (int) (clone $quotesQuery)->whereBetween('created_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])->count();
        $totalQuotesValueMonth = (string) (clone $quotesQuery)->whereBetween('created_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])->sum('total_amount');

        $approvedQuotesMonth = (clone $quotesQuery)
            ->whereHas('status', function ($q) {
                $q->where('slug', 'like', '%aprovado%')
                  ->orWhere('name', 'ilike', '%aprovado%');
            })
            ->whereBetween('created_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59']);
        $approvedCount = (int) (clone $approvedQuotesMonth)->count();
        $approvedValue = (string) (clone $approvedQuotesMonth)->sum('total_amount');

        $conversionRate = $totalQuotesMonth > 0 ? round(($approvedCount / $totalQuotesMonth) * 100, 1) : 0.0;

        // Distribuição de status dos orçamentos
        $quotesStatusDistribution = Quote::where('quotes.tenant_id', $tenant->id)
            ->join('tenant_statuses', 'tenant_statuses.id', '=', 'quotes.status_id')
            ->select('tenant_statuses.name as status', DB::raw('count(quotes.id) as count'), DB::raw('sum(quotes.total_amount) as total'))
            ->groupBy('tenant_statuses.name')
            ->get();

        $recentQuotes = Quote::where('tenant_id', $tenant->id)
            ->with(['customer:id,name', 'status:id,name,color'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'quote_number', 'customer_id', 'status_id', 'title', 'total_amount', 'created_at']);

        // ---------------------------------------------------------------------
        // 3. CHÃO DE FÁBRICA & ENGENHARIA (PCP)
        // ---------------------------------------------------------------------
        $activeOrdersCount = (int) ProductionOrder::where('tenant_id', $tenant->id)
            ->whereDoesntHave('status', function ($q) {
                $q->where('is_final', true);
            })
            ->count();

        $completedOrdersMonth = (int) ProductionOrder::where('tenant_id', $tenant->id)
            ->whereHas('status', function ($q) {
                $q->where('is_final', true);
            })
            ->whereBetween('updated_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])
            ->count();

        $ordersStatusDistribution = ProductionOrder::where('production_orders.tenant_id', $tenant->id)
            ->join('tenant_statuses', 'tenant_statuses.id', '=', 'production_orders.status_id')
            ->select('tenant_statuses.name as status', DB::raw('count(production_orders.id) as count'))
            ->groupBy('tenant_statuses.name')
            ->get();

        $recentOrders = ProductionOrder::where('tenant_id', $tenant->id)
            ->with(['bom:id,name', 'status:id,name,color'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'order_number', 'bom_id', 'status_id', 'product_name', 'target_quantity', 'produced_quantity', 'due_date', 'created_at']);

        // ---------------------------------------------------------------------
        // 4. ESTOQUE & INSUMOS
        // ---------------------------------------------------------------------
        $totalItemsCount = (int) Item::where('tenant_id', $tenant->id)->where('is_active', true)->count();
        $totalStockValue = (string) InventoryBalance::where('tenant_id', $tenant->id)->sum('total_value');

        // Itens com estoque baixo (quantidade física <= 10 ou ponto de reposição)
        $lowStockBalances = InventoryBalance::where('tenant_id', $tenant->id)
            ->with('item:id,code,name,unit_of_measure')
            ->where('quantity_on_hand', '<=', 10)
            ->limit(5)
            ->get();

        return Inertia::render('Tenant/Dashboard/Index', [
            'tenant' => $tenant,
            'kpis' => [
                'financial' => [
                    'revenue_month' => $totalReceivableMonth,
                    'received_month' => $receivedMonth,
                    'payable_month' => $totalPayableMonth,
                    'paid_month' => $paidMonth,
                    'pending_receivable' => $pendingReceivable,
                    'pending_payable' => $pendingPayable,
                    'total_bank_balance' => $totalBankBalance,
                    'bank_accounts' => $bankAccounts,
                    'history' => $historyMonths,
                ],
                'sales' => [
                    'quotes_count' => $totalQuotesMonth,
                    'quotes_value' => $totalQuotesValueMonth,
                    'approved_count' => $approvedCount,
                    'approved_value' => $approvedValue,
                    'conversion_rate' => $conversionRate,
                    'status_distribution' => $quotesStatusDistribution,
                    'recent_quotes' => $recentQuotes,
                ],
                'production' => [
                    'active_orders' => $activeOrdersCount,
                    'completed_month' => $completedOrdersMonth,
                    'status_distribution' => $ordersStatusDistribution,
                    'recent_orders' => $recentOrders,
                ],
                'inventory' => [
                    'total_items' => $totalItemsCount,
                    'total_stock_value' => $totalStockValue,
                    'low_stock_items' => $lowStockBalances,
                ],
            ],
        ]);
    }
}
