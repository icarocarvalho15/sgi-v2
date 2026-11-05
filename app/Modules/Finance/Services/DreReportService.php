<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\FinancialEntry;
use Carbon\Carbon;

class DreReportService
{
    /**
     * Gera a DRE Gerencial (Demonstrativo do Resultado do Exercício) por Regime de Competência.
     *
     * @return array{
     *     period_start: string,
     *     period_end: string,
     *     gross_revenue: string,
     *     cost_of_goods: string,
     *     gross_profit: string,
     *     operating_expenses: string,
     *     net_profit: string,
     *     margin_percentage: string
     * }
     */
    public function generate(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Receita Bruta (Receitas de Vendas / Serviços gráficos)
        $grossRevenue = (string) FinancialEntry::whereBetween('competence_date', [$start->toDateString(), $end->toDateString()])
            ->where('type', 'receivable')
            ->where('status', '!=', 'cancelled')
            ->sum('net_amount');

        // Custos dos Produtos Vendidos (Insumos, papéis, chapas, tintas, terceirizações)
        $costOfGoods = (string) FinancialEntry::whereBetween('competence_date', [$start->toDateString(), $end->toDateString()])
            ->where('type', 'payable')
            ->where('status', '!=', 'cancelled')
            ->whereHas('chartOfAccount', fn ($q) => $q->where('type', 'cost_of_goods'))
            ->sum('net_amount');

        // Lucro Bruto = Receita - CPV
        $grossProfit = bcsub($grossRevenue, $costOfGoods, 4);

        // Despesas Operacionais (Administrativas, Comerciais, Financeiras)
        $operatingExpenses = (string) FinancialEntry::whereBetween('competence_date', [$start->toDateString(), $end->toDateString()])
            ->where('type', 'payable')
            ->where('status', '!=', 'cancelled')
            ->whereHas('chartOfAccount', fn ($q) => $q->where('type', 'expense'))
            ->sum('net_amount');

        // Resultado Líquido = Lucro Bruto - Despesas Operacionais
        $netProfit = bcsub($grossProfit, $operatingExpenses, 4);

        // Margem Líquida %
        $marginPercentage = '0.00';
        if (bccomp($grossRevenue, '0.0000', 4) > 0) {
            $marginPercentage = bcdiv(bcmul($netProfit, '100', 4), $grossRevenue, 2);
        }

        return [
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'gross_revenue' => $grossRevenue,
            'cost_of_goods' => $costOfGoods,
            'gross_profit' => $grossProfit,
            'operating_expenses' => $operatingExpenses,
            'net_profit' => $netProfit,
            'margin_percentage' => $marginPercentage,
        ];
    }
}
