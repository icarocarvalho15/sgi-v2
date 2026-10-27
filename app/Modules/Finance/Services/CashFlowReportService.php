<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Enums\EntryType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Models\FinancialPayment;
use Carbon\Carbon;

final class CashFlowReportService
{
    /**
     * @return array{
     *     initial_balance: string,
     *     current_balance: string,
     *     realized_inflow: string,
     *     realized_outflow: string,
     *     projected_inflow: string,
     *     projected_outflow: string,
     *     projected_final_balance: string,
     *     daily_timeline: array<int, array{
     *         date: string,
     *         realized_inflow: string,
     *         realized_outflow: string,
     *         projected_inflow: string,
     *         projected_outflow: string,
     *         balance: string
     *     }>
     * }
     */
    public function generate(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->addDays(30)->endOfDay();

        $currentBalance = (string) FinancialAccount::where('is_active', true)->sum('current_balance');

        // Pagamentos Realizados (Recebidos e Pagos)
        $payments = FinancialPayment::with('entry')
            ->where('is_reversed', false)
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $realizedInflow = '0.00';
        $realizedOutflow = '0.00';

        foreach ($payments as $p) {
            if ($p->entry?->entry_type === EntryType::Receivable) {
                $realizedInflow = bcadd($realizedInflow, (string) $p->net_amount, 2);
            } else {
                $realizedOutflow = bcadd($realizedOutflow, (string) $p->net_amount, 2);
            }
        }

        // Títulos Projetados em Aberto
        $pendingEntries = FinancialEntry::whereIn('status', ['PENDING', 'PARTIALLY_PAID'])
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $projectedInflow = '0.00';
        $projectedOutflow = '0.00';

        foreach ($pendingEntries as $e) {
            if ($e->entry_type === EntryType::Receivable) {
                $projectedInflow = bcadd($projectedInflow, (string) $e->balance_amount, 2);
            } else {
                $projectedOutflow = bcadd($projectedOutflow, (string) $e->balance_amount, 2);
            }
        }

        // Projeção Final = Saldo Atual + Projetado a Receber - Projetado a Pagar
        $projectedFinal = bcsub(bcadd($currentBalance, $projectedInflow, 2), $projectedOutflow, 2);

        // Agrupamento Diário
        $daily = [];
        $runningBalance = $currentBalance;

        $period = $start->copy();
        while ($period->lte($end)) {
            $dateStr = $period->toDateString();

            $dayRealIn = '0.00';
            $dayRealOut = '0.00';
            foreach ($payments as $p) {
                if ($p->payment_date?->toDateString() === $dateStr) {
                    if ($p->entry?->entry_type === EntryType::Receivable) {
                        $dayRealIn = bcadd($dayRealIn, (string) $p->net_amount, 2);
                    } else {
                        $dayRealOut = bcadd($dayRealOut, (string) $p->net_amount, 2);
                    }
                }
            }

            $dayProjIn = '0.00';
            $dayProjOut = '0.00';
            foreach ($pendingEntries as $e) {
                if ($e->due_date?->toDateString() === $dateStr) {
                    if ($e->entry_type === EntryType::Receivable) {
                        $dayProjIn = bcadd($dayProjIn, (string) $e->balance_amount, 2);
                    } else {
                        $dayProjOut = bcadd($dayProjOut, (string) $e->balance_amount, 2);
                    }
                }
            }

            // O saldo diário considera as projeções futuras
            $runningBalance = bcsub(bcadd($runningBalance, $dayProjIn, 2), $dayProjOut, 2);

            $daily[] = [
                'date' => $dateStr,
                'realized_inflow' => $dayRealIn,
                'realized_outflow' => $dayRealOut,
                'projected_inflow' => $dayProjIn,
                'projected_outflow' => $dayProjOut,
                'balance' => $runningBalance,
            ];

            $period->addDay();
        }

        return [
            'initial_balance' => $currentBalance,
            'current_balance' => $currentBalance,
            'realized_inflow' => $realizedInflow,
            'realized_outflow' => $realizedOutflow,
            'projected_inflow' => $projectedInflow,
            'projected_outflow' => $projectedOutflow,
            'projected_final_balance' => $projectedFinal,
            'daily_timeline' => $daily,
        ];
    }
}
