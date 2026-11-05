<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Models\FinancialPayment;
use Carbon\Carbon;

class CashFlowReportService
{
    /**
     * Gera o Fluxo de Caixa (Realizado vs Projetado) por período.
     *
     * @return array{
     *     period_start: string,
     *     period_end: string,
     *     realized_inflows: string,
     *     realized_outflows: string,
     *     realized_net: string,
     *     projected_inflows: string,
     *     projected_outflows: string,
     *     projected_net: string
     * }
     */
    public function generate(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Realizado: pagamentos efetivados no período
        $realizedInflows = (string) FinancialPayment::whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('entry', fn ($q) => $q->where('type', 'receivable'))
            ->sum('amount');

        $realizedOutflows = (string) FinancialPayment::whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('entry', fn ($q) => $q->where('type', 'payable'))
            ->sum('amount');

        $realizedNet = bcsub($realizedInflows, $realizedOutflows, 4);

        // Projetado: títulos em aberto com vencimento no período
        $projectedInflows = (string) FinancialEntry::whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->where('type', 'receivable')
            ->whereIn('status', ['pending', 'partial'])
            ->sum('balance_due');

        $projectedOutflows = (string) FinancialEntry::whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->where('type', 'payable')
            ->whereIn('status', ['pending', 'partial'])
            ->sum('balance_due');

        $projectedNet = bcsub($projectedInflows, $projectedOutflows, 4);

        return [
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'realized_inflows' => $realizedInflows,
            'realized_outflows' => $realizedOutflows,
            'realized_net' => $realizedNet,
            'projected_inflows' => $projectedInflows,
            'projected_outflows' => $projectedOutflows,
            'projected_net' => $projectedNet,
        ];
    }
}
