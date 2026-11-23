import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../../../Layouts/AppLayout';
import { 
    TrendingUp, 
    DollarSign, 
    ArrowDownLeft, 
    ArrowUpRight, 
    Calendar, 
    FileSpreadsheet, 
    ArrowLeft,
    CheckCircle2,
    PieChart,
    Download
} from 'lucide-react';
import { exportCashFlowAndDreToCsv } from '../../../../Utils/exportToCsv';

interface Props {
    cashFlow: {
        period_start: string;
        period_end: string;
        realized_inflows: string;
        realized_outflows: string;
        realized_net: string;
        projected_inflows: string;
        projected_outflows: string;
        projected_net: string;
    };
    dre: {
        period_start: string;
        period_end: string;
        gross_revenue: string;
        cost_of_goods: string;
        gross_profit: string;
        operating_expenses: string;
        net_profit: string;
        margin_percentage: string;
    };
    filters: {
        start_date: string;
        end_date: string;
    };
}

export default function CashFlowReport({ cashFlow, dre, filters }: Props) {
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/finance/reports', {
            start_date: startDate,
            end_date: endDate,
        }, { preserveState: true });
    };

    const formatCurrency = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num || 0);
    };

    return (
        <AppLayout title="Fluxo de Caixa & DRE Gerencial">
            <Head title="Relatórios Financeiros - SGI v2.0" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <Link
                            href="/finance/entries"
                            className="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-200 transition-colors mb-2"
                        >
                            <ArrowLeft className="w-3.5 h-3.5" />
                            Voltar para Títulos Financeiros
                        </Link>
                        <h1 className="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                            <TrendingUp className="w-7 h-7 text-indigo-400" />
                            Fluxo de Caixa & DRE Gerencial
                        </h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Análise de liquidez financeira realizada vs projetada e resultado econômico por competência.
                        </p>
                    </div>

                    {/* Actions & Date filter form */}
                    <div className="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            onClick={() => exportCashFlowAndDreToCsv(cashFlow, dre)}
                            className="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-xl text-xs font-semibold transition-colors shadow-sm"
                            title="Exportar demonstrativo financeiro em CSV compatível com Excel"
                        >
                            <Download className="w-3.5 h-3.5 text-indigo-400" />
                            Exportar Planilha (CSV)
                        </button>

                        <form onSubmit={handleFilter} className="flex items-center gap-2 bg-slate-900 p-2 rounded-xl border border-slate-800">
                            <input
                                type="date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                                className="bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                            />
                            <span className="text-slate-500 text-xs">até</span>
                            <input
                                type="date"
                                value={endDate}
                                onChange={(e) => setEndDate(e.target.value)}
                                className="bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                            />
                            <button
                                type="submit"
                                className="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium rounded-lg transition-colors"
                            >
                                Filtrar
                            </button>
                        </form>
                    </div>
                </div>

                {/* Section 1: Fluxo de Caixa Realizado vs Projetado */}
                <div className="space-y-3">
                    <h2 className="text-base font-bold text-white flex items-center gap-2">
                        <DollarSign className="w-5 h-5 text-emerald-400" />
                        Fluxo de Caixa no Período
                    </h2>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {/* Realizado Card */}
                        <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
                            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                                <div>
                                    <span className="text-xs uppercase font-semibold text-emerald-400 tracking-wider">
                                        Regime de Caixa (Realizado)
                                    </span>
                                    <h3 className="text-lg font-bold text-white mt-0.5">Valores Efetivamente Baixados</h3>
                                </div>
                                <span className="p-2 rounded-lg bg-emerald-500/10 text-emerald-400">
                                    <CheckCircle2 className="w-5 h-5" />
                                </span>
                            </div>

                            <div className="space-y-2 text-sm font-mono">
                                <div className="flex justify-between items-center text-slate-300">
                                    <span className="flex items-center gap-1.5 text-emerald-400 font-sans text-xs">
                                        <ArrowDownLeft className="w-4 h-4" /> Entradas / Recebimentos:
                                    </span>
                                    <span className="text-emerald-400 font-bold">{formatCurrency(cashFlow.realized_inflows)}</span>
                                </div>
                                <div className="flex justify-between items-center text-slate-300">
                                    <span className="flex items-center gap-1.5 text-rose-400 font-sans text-xs">
                                        <ArrowUpRight className="w-4 h-4" /> Saídas / Pagamentos:
                                    </span>
                                    <span className="text-rose-400 font-bold">-{formatCurrency(cashFlow.realized_outflows)}</span>
                                </div>
                                <div className="pt-3 border-t border-slate-800 flex justify-between items-center text-base">
                                    <span className="font-sans font-semibold text-white text-sm">Saldo Líquido Realizado:</span>
                                    <span className={`font-bold ${parseFloat(cashFlow.realized_net) >= 0 ? 'text-emerald-400' : 'text-rose-400'}`}>
                                        {formatCurrency(cashFlow.realized_net)}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Projetado Card */}
                        <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
                            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                                <div>
                                    <span className="text-xs uppercase font-semibold text-sky-400 tracking-wider">
                                        Previsão de Caixa (Projetado)
                                    </span>
                                    <h3 className="text-lg font-bold text-white mt-0.5">Títulos em Aberto com Vencimento</h3>
                                </div>
                                <span className="p-2 rounded-lg bg-sky-500/10 text-sky-400">
                                    <TrendingUp className="w-5 h-5" />
                                </span>
                            </div>

                            <div className="space-y-2 text-sm font-mono">
                                <div className="flex justify-between items-center text-slate-300">
                                    <span className="flex items-center gap-1.5 text-sky-400 font-sans text-xs">
                                        <ArrowDownLeft className="w-4 h-4" /> Previsão a Receber:
                                    </span>
                                    <span className="text-sky-400 font-bold">{formatCurrency(cashFlow.projected_inflows)}</span>
                                </div>
                                <div className="flex justify-between items-center text-slate-300">
                                    <span className="flex items-center gap-1.5 text-amber-400 font-sans text-xs">
                                        <ArrowUpRight className="w-4 h-4" /> Previsão a Pagar:
                                    </span>
                                    <span className="text-amber-400 font-bold">-{formatCurrency(cashFlow.projected_outflows)}</span>
                                </div>
                                <div className="pt-3 border-t border-slate-800 flex justify-between items-center text-base">
                                    <span className="font-sans font-semibold text-white text-sm">Saldo Projetado:</span>
                                    <span className={`font-bold ${parseFloat(cashFlow.projected_net) >= 0 ? 'text-sky-400' : 'text-amber-400'}`}>
                                        {formatCurrency(cashFlow.projected_net)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Section 2: DRE Gerencial */}
                <div className="space-y-3 pt-4">
                    <h2 className="text-base font-bold text-white flex items-center gap-2">
                        <PieChart className="w-5 h-5 text-indigo-400" />
                        DRE Gerencial (Regime de Competência)
                    </h2>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                        <table className="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr className="border-b border-slate-800 bg-slate-950/50 text-slate-400 text-xs uppercase font-semibold">
                                    <th className="py-3 px-6">Estrutura Contábil / Demonstrativo</th>
                                    <th className="py-3 px-6 text-right">Valor no Período (R$)</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60 font-mono text-sm">
                                <tr className="hover:bg-slate-800/30">
                                    <td className="py-3 px-6 font-sans font-medium text-slate-200">
                                        (+) Receita Bruta Operacional (Vendas & Serviços)
                                    </td>
                                    <td className="py-3 px-6 text-right font-bold text-emerald-400">
                                        {formatCurrency(dre.gross_revenue)}
                                    </td>
                                </tr>
                                <tr className="hover:bg-slate-800/30">
                                    <td className="py-3 px-6 font-sans text-slate-300 pl-10">
                                        (-) Custos dos Produtos Vendidos (Insumos, Papéis, Chapas CTP)
                                    </td>
                                    <td className="py-3 px-6 text-right text-rose-400">
                                        -{formatCurrency(dre.cost_of_goods)}
                                    </td>
                                </tr>
                                <tr className="bg-slate-950/40 font-bold">
                                    <td className="py-3 px-6 font-sans text-slate-100">
                                        (=) Lucro Bruto Operacional
                                    </td>
                                    <td className="py-3 px-6 text-right text-white">
                                        {formatCurrency(dre.gross_profit)}
                                    </td>
                                </tr>
                                <tr className="hover:bg-slate-800/30">
                                    <td className="py-3 px-6 font-sans text-slate-300 pl-10">
                                        (-) Despesas Operacionais (Comercial, ADM, Infraestrutura)
                                    </td>
                                    <td className="py-3 px-6 text-right text-rose-400">
                                        -{formatCurrency(dre.operating_expenses)}
                                    </td>
                                </tr>
                                <tr className="bg-indigo-950/30 border-t-2 border-indigo-500 font-bold text-base">
                                    <td className="py-4 px-6 font-sans text-white flex items-center justify-between">
                                        <span>(=) Resultado Líquido do Período</span>
                                        <span className="text-xs font-mono px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                            Margem: {dre.margin_percentage}%
                                        </span>
                                    </td>
                                    <td className={`py-4 px-6 text-right ${parseFloat(dre.net_profit) >= 0 ? 'text-emerald-400' : 'text-rose-400'}`}>
                                        {formatCurrency(dre.net_profit)}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
