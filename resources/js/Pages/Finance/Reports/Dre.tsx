import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import FinancialSubNav from '@/Pages/Finance/Components/FinancialSubNav';
import { 
    FileSpreadsheet, 
    Calendar, 
    TrendingUp, 
    TrendingDown, 
    PieChart, 
    Percent, 
    DollarSign,
    CheckCircle2
} from 'lucide-react';

interface CategoryBreakdown {
    code: string;
    name: string;
    total: string;
    percentage: string;
}

interface DreReport {
    regime: 'competence' | 'cash';
    start_date: string;
    end_date: string;
    gross_revenue: string;
    total_expenses: string;
    net_result: string;
    revenue_categories: CategoryBreakdown[];
    expense_categories: CategoryBreakdown[];
}

interface Props {
    report: DreReport;
    filters: {
        regime?: string;
        start_date?: string;
        end_date?: string;
    };
}

export default function Dre({ report, filters }: Props) {
    const [regime, setRegime] = useState(filters.regime || 'competence');
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');

    const applyFilters = (newRegime?: string) => {
        router.get('/app/finance/reports/dre', {
            regime: newRegime || regime,
            start_date: startDate,
            end_date: endDate,
        }, { preserveState: true, replace: true });
    };

    const formatBRL = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num || 0);
    };

    const isProfit = parseFloat(report.net_result) >= 0;
    const marginPct = parseFloat(report.gross_revenue) > 0 
        ? ((parseFloat(report.net_result) / parseFloat(report.gross_revenue)) * 100).toFixed(2)
        : '0.00';

    return (
        <TenantDesktopLayout title="Demonstrativo do Resultado do Exercício (DRE)">
            <Head title="DRE Gerencial - SGI" />

            <div className="p-6 max-w-7xl mx-auto space-y-6">
                <FinancialSubNav />

                {/* Header & Regime Switcher */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center space-x-3">
                        <div className="p-2 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-lg">
                            <FileSpreadsheet className="w-5 h-5" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-white tracking-tight">
                                DRE Gerencial da Fábrica
                            </h1>
                            <p className="text-xs text-slate-400">
                                Análise estruturada de receitas, custos e despesas por competência ou caixa.
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center space-x-3">
                        {/* Seletor de Regime */}
                        <div className="flex bg-slate-900 border border-slate-800 rounded-lg p-1 text-xs">
                            <button
                                onClick={() => {
                                    setRegime('competence');
                                    applyFilters('competence');
                                }}
                                className={`px-3 py-1.5 rounded-md font-semibold transition ${
                                    regime === 'competence'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                }`}
                            >
                                Regime de Competência
                            </button>
                            <button
                                onClick={() => {
                                    setRegime('cash');
                                    applyFilters('cash');
                                }}
                                className={`px-3 py-1.5 rounded-md font-semibold transition ${
                                    regime === 'cash'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                }`}
                            >
                                Regime de Caixa
                            </button>
                        </div>

                        {/* Filtro Data */}
                        <div className="flex items-center space-x-2 bg-slate-900 border border-slate-800 rounded-lg p-1.5">
                            <Calendar className="w-4 h-4 text-slate-400 ml-2" />
                            <input
                                type="date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                                className="bg-transparent text-xs text-slate-200 focus:outline-none"
                            />
                            <span className="text-xs text-slate-400">até</span>
                            <input
                                type="date"
                                value={endDate}
                                onChange={(e) => setEndDate(e.target.value)}
                                className="bg-transparent text-xs text-slate-200 focus:outline-none"
                            />
                            <button
                                onClick={() => applyFilters()}
                                className="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded transition"
                            >
                                Filtrar
                            </button>
                        </div>
                    </div>
                </div>

                {/* Cards de Indicadores de Resultado */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <span className="text-xs text-slate-400 flex items-center justify-between">
                            Receita Bruta Operacional
                            <TrendingUp className="w-4 h-4 text-emerald-400" />
                        </span>
                        <p className="text-2xl font-bold text-emerald-400 mt-2">
                            {formatBRL(report.gross_revenue)}
                        </p>
                        <span className="text-[11px] text-slate-400">Total faturado no período</span>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <span className="text-xs text-slate-400 flex items-center justify-between">
                            Custos & Despesas Totais
                            <TrendingDown className="w-4 h-4 text-rose-400" />
                        </span>
                        <p className="text-2xl font-bold text-rose-400 mt-2">
                            {formatBRL(report.total_expenses)}
                        </p>
                        <span className="text-[11px] text-slate-400">Matéria-prima, fixos e variáveis</span>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <span className="text-xs text-slate-400 flex items-center justify-between">
                            Resultado Líquido do Período
                            <DollarSign className={`w-4 h-4 ${isProfit ? 'text-emerald-400' : 'text-rose-400'}`} />
                        </span>
                        <p className={`text-2xl font-bold mt-2 ${isProfit ? 'text-emerald-400' : 'text-rose-400'}`}>
                            {formatBRL(report.net_result)}
                        </p>
                        <span className="text-[11px] font-semibold text-slate-400">
                            {isProfit ? 'Lucro Líquido Operacional' : 'Prejuízo Operacional'}
                        </span>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <span className="text-xs text-slate-400 flex items-center justify-between">
                            Margem Líquida da Operação
                            <Percent className="w-4 h-4 text-indigo-400" />
                        </span>
                        <p className={`text-2xl font-bold mt-2 ${parseFloat(marginPct) >= 0 ? 'text-indigo-400' : 'text-rose-400'}`}>
                            {marginPct}%
                        </p>
                        <span className="text-[11px] text-slate-400">Sobre a Receita Bruta</span>
                    </div>
                </div>

                {/* Estrutura Analítica da DRE */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Receitas */}
                    <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                        <div className="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                            <h2 className="text-sm font-bold text-emerald-400 flex items-center space-x-2">
                                <TrendingUp className="w-4 h-4" />
                                <span>(+) Receitas Operacionais</span>
                            </h2>
                            <span className="text-xs font-bold text-white">
                                {formatBRL(report.gross_revenue)}
                            </span>
                        </div>

                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-950/60 text-slate-400 font-semibold border-b border-slate-800 uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-2.5 px-4">Código</th>
                                    <th className="py-2.5 px-4">Categoria</th>
                                    <th className="py-2.5 px-4 text-right">Total</th>
                                    <th className="py-2.5 px-4 text-right">% Rec.</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {report.revenue_categories.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="py-8 text-center text-slate-400">
                                            Nenhuma receita registrada no período.
                                        </td>
                                    </tr>
                                ) : (
                                    report.revenue_categories.map((cat) => (
                                        <tr key={cat.code} className="hover:bg-slate-800/40 transition">
                                            <td className="py-2.5 px-4 font-mono text-slate-300">{cat.code}</td>
                                            <td className="py-2.5 px-4 font-medium text-white">{cat.name}</td>
                                            <td className="py-2.5 px-4 text-right font-semibold text-emerald-400">{formatBRL(cat.total)}</td>
                                            <td className="py-2.5 px-4 text-right text-slate-400 font-mono">{cat.percentage}%</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Despesas & Custos */}
                    <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                        <div className="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/40">
                            <h2 className="text-sm font-bold text-rose-400 flex items-center space-x-2">
                                <TrendingDown className="w-4 h-4" />
                                <span>(-) Custos & Despesas</span>
                            </h2>
                            <span className="text-xs font-bold text-white">
                                {formatBRL(report.total_expenses)}
                            </span>
                        </div>

                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-950/60 text-slate-400 font-semibold border-b border-slate-800 uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-2.5 px-4">Código</th>
                                    <th className="py-2.5 px-4">Categoria</th>
                                    <th className="py-2.5 px-4 text-right">Total</th>
                                    <th className="py-2.5 px-4 text-right">% Desp.</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {report.expense_categories.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="py-8 text-center text-slate-400">
                                            Nenhuma despesa registrada no período.
                                        </td>
                                    </tr>
                                ) : (
                                    report.expense_categories.map((cat) => (
                                        <tr key={cat.code} className="hover:bg-slate-800/40 transition">
                                            <td className="py-2.5 px-4 font-mono text-slate-300">{cat.code}</td>
                                            <td className="py-2.5 px-4 font-medium text-white">{cat.name}</td>
                                            <td className="py-2.5 px-4 text-right font-semibold text-rose-400">{formatBRL(cat.total)}</td>
                                            <td className="py-2.5 px-4 text-right text-slate-400 font-mono">{cat.percentage}%</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </TenantDesktopLayout>
    );
}
