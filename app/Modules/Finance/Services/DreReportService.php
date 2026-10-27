<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Enums\AccountCategoryType;
use App\Modules\Finance\Enums\EntryStatus;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Models\FinancialPayment;
use Carbon\Carbon;

final class DreReportService
{
    /**
     * @return array{
     *     regime: string,
     *     start_date: string,
     *     end_date: string,
     *     gross_revenue: string,
     *     total_expenses: string,
     *     net_result: string,
     *     revenue_categories: array<int, array{code: string, name: string, total: string, percentage: string}>,
     *     expense_categories: array<int, array{code: string, name: string, total: string, percentage: string}>
     * }
     */
    public function generate(string $regime = 'competence', ?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfYear();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfYear();

        $grossRevenue = '0.00';
        $totalExpenses = '0.00';

        $revenueMap = [];
        $expenseMap = [];

        if ($regime === 'cash') {
            // Regime de Caixa: baseado em pagamentos compensados
            $payments = FinancialPayment::with(['entry.chartOfAccount'])
                ->where('is_reversed', false)
                ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
                ->get();

            foreach ($payments as $p) {
                $account = $p->entry?->chartOfAccount;
                if (! $account) {
                    continue;
                }

                $amount = (string) $p->net_amount;

                if ($account->type === AccountCategoryType::Revenue) {
                    $grossRevenue = bcadd($grossRevenue, $amount, 2);
                    $revenueMap[$account->id] = [
                        'code' => $account->code,
                        'name' => $account->name,
                        'total' => bcadd($revenueMap[$account->id]['total'] ?? '0.00', $amount, 2),
                    ];
                } else {
                    $totalExpenses = bcadd($totalExpenses, $amount, 2);
                    $expenseMap[$account->id] = [
                        'code' => $account->code,
                        'name' => $account->name,
                        'total' => bcadd($expenseMap[$account->id]['total'] ?? '0.00', $amount, 2),
                    ];
                }
            }
        } else {
            // Regime de Competência: baseado na data de competência dos títulos
            $entries = FinancialEntry::with('chartOfAccount')
                ->where('status', '!=', EntryStatus::Cancelled)
                ->whereBetween('competence_date', [$start->toDateString(), $end->toDateString()])
                ->get();

            foreach ($entries as $e) {
                $account = $e->chartOfAccount;
                if (! $account) {
                    continue;
                }

                $amount = (string) $e->original_amount;

                if ($account->type === AccountCategoryType::Revenue) {
                    $grossRevenue = bcadd($grossRevenue, $amount, 2);
                    $revenueMap[$account->id] = [
                        'code' => $account->code,
                        'name' => $account->name,
                        'total' => bcadd($revenueMap[$account->id]['total'] ?? '0.00', $amount, 2),
                    ];
                } else {
                    $totalExpenses = bcadd($totalExpenses, $amount, 2);
                    $expenseMap[$account->id] = [
                        'code' => $account->code,
                        'name' => $account->name,
                        'total' => bcadd($expenseMap[$account->id]['total'] ?? '0.00', $amount, 2),
                    ];
                }
            }
        }

        $netResult = bcsub($grossRevenue, $totalExpenses, 2);

        // Formata listas com percentuais
        $revenueList = [];
        foreach ($revenueMap as $item) {
            $pct = '0.00';
            if (bccomp($grossRevenue, '0.00', 2) > 0) {
                $pct = number_format(((float) $item['total'] / (float) $grossRevenue) * 100, 2, '.', '');
            }
            $revenueList[] = [
                'code' => $item['code'],
                'name' => $item['name'],
                'total' => $item['total'],
                'percentage' => $pct,
            ];
        }

        $expenseList = [];
        foreach ($expenseMap as $item) {
            $pct = '0.00';
            if (bccomp($totalExpenses, '0.00', 2) > 0) {
                $pct = number_format(((float) $item['total'] / (float) $totalExpenses) * 100, 2, '.', '');
            }
            $expenseList[] = [
                'code' => $item['code'],
                'name' => $item['name'],
                'total' => $item['total'],
                'percentage' => $pct,
            ];
        }

        return [
            'regime' => $regime,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'gross_revenue' => $grossRevenue,
            'total_expenses' => $totalExpenses,
            'net_result' => $netResult,
            'revenue_categories' => $revenueList,
            'expense_categories' => $expenseList,
        ];
    }
}
