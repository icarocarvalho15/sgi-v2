import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import FinancialSubNav from '@/Pages/Finance/Components/FinancialSubNav';
import { 
    Activity, 
    Calendar, 
    ArrowUpRight, 
    ArrowDownRight, 
    Wallet, 
    TrendingUp, 
    Filter,
    BarChart3
} from 'lucide-react';

interface DailyTimelineItem {
    date: string;
    realized_inflow: string;
    realized_outflow: string;
    projected_inflow: string;
    projected_outflow: string;
    balance: string;
}

interface CashFlowReport {
    initial_balance: string;
    current_balance: string;
    realized_inflow: string;
    realized_outflow: string;
    projected_inflow: string;
    projected_outflow: string;
    projected_final_balance: string;
    daily_timeline: DailyTimelineItem[];
}

interface Props {
    report: CashFlowReport;
    filters: {
        start_date?: string;
        end_date?: string;
    };
}

export default function CashFlow({ report, filters }: Props) {
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');

    const applyFilters = () => {
        router.get('/app/finance/reports/cash-flow', {
            start_date: startDate,
            end_date: endDate,
        }, { preserveState: true, replace: true });
    };

    const formatBRL = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num || 0);
    };

    return (
        <TenantDesktopLayout title="Fluxo de Caixa Operacional">
            <Head title="Fluxo de Caixa - SGI" />

            <div className="p-6 max-w-7xl mx-auto space-y-6">
                <FinancialSubNav />

                {/* Header & Filtro de Período */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center space-x-3">
                        <div className="p-2 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-lg">
                            <Activity className="w-5 h-5" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-white tracking-tight">
                                Fluxo de Caixa (Realizado vs Projetado)
                            </h1>
                            <p className="text-xs text-slate-400">
                                Visão consolidada da liquidez atual e projeção de entradas e saídas diárias.
                            </p>
                        </div>
                    </div>

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
                            onClick={applyFilters}
                            className="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded transition"
                        >
                            Filtrar
                        </button>
                    </div>
                </div>

                {/* Grid de KPIs Financeiros */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {/* Saldo Atual */}
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div className="flex items-center justify-between text-slate-400 text-xs">
                            <span>Saldo Atual em Contas</span>
                            <Wallet className="w-4 h-4 text-indigo-400" />
                        </div>
                        <p className="text-2xl font-bold text-white mt-2">
                            {formatBRL(report.current_balance)}
                        </p>
                        <span className="text-[11px] text-slate-400">Disponível imediatamente</span>
                    </div>

                    {/* Realizado (Entradas vs Saídas) */}
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div className="flex items-center justify-between text-slate-400 text-xs">
                            <span>Movimentação Realizada no Período</span>
                            <TrendingUp className="w-4 h-4 text-emerald-400" />
                        </div>
                        <div className="flex items-center justify-between mt-2">
                            <div>
                                <span className="text-[10px] text-emerald-400 font-semibold block">+ Recebido</span>
                                <span className="text-base font-bold text-emerald-400">{formatBRL(report.realized_inflow)}</span>
                            </div>
                            <div className="text-right">
                                <span className="text-[10px] text-rose-400 font-semibold block">- Pago</span>
                                <span className="text-base font-bold text-rose-400">{formatBRL(report.realized_outflow)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Projeção Final */}
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div className="flex items-center justify-between text-slate-400 text-xs">
                            <span>Saldo Final Projetado</span>
                            <BarChart3 className="w-4 h-4 text-indigo-400" />
                        </div>
                        <p className={`text-2xl font-bold mt-2 ${parseFloat(report.projected_final_balance) >= 0 ? 'text-indigo-400' : 'text-rose-400'}`}>
                            {formatBRL(report.projected_final_balance)}
                        </p>
                        <div className="flex items-center justify-between text-[11px] text-slate-400 mt-1">
                            <span>Prev. +{formatBRL(report.projected_inflow)}</span>
                            <span>Prev. -{formatBRL(report.projected_outflow)}</span>
                        </div>
                    </div>
                </div>

                {/* Timeline Diária do Fluxo de Caixa */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div className="p-4 border-b border-slate-800 flex items-center justify-between">
                        <h2 className="text-sm font-bold text-white">Evolução Cronológica Diária</h2>
                        <span className="text-xs text-slate-400">
                            {report.daily_timeline.length} dias analisados
                        </span>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-950/60 text-slate-400 font-semibold border-b border-slate-800 uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3 px-4">Data</th>
                                    <th className="py-3 px-4 text-right text-emerald-400">Entradas Realizadas (+)</th>
                                    <th className="py-3 px-4 text-right text-rose-400">Saídas Realizadas (-)</th>
                                    <th className="py-3 px-4 text-right text-blue-400">Entradas Previstas (+)</th>
                                    <th className="py-3 px-4 text-right text-amber-400">Saídas Previstas (-)</th>
                                    <th className="py-3 px-4 text-right text-white">Saldo Acumulado</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {report.daily_timeline.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="py-12 text-center text-slate-400">
                                            Nenhuma movimentação para o período selecionado.
                                        </td>
                                    </tr>
                                ) : (
                                    report.daily_timeline.map((day) => {
                                        const hasMovement = parseFloat(day.realized_inflow) > 0 || 
                                                            parseFloat(day.realized_outflow) > 0 || 
                                                            parseFloat(day.projected_inflow) > 0 || 
                                                            parseFloat(day.projected_outflow) > 0;

                                        return (
                                            <tr key={day.date} className={hasMovement ? 'bg-slate-800/20 hover:bg-slate-800/40 transition' : 'hover:bg-slate-800/10'}>
                                                <td className="py-3 px-4 font-mono font-medium text-slate-200">
                                                    {new Date(day.date + 'T00:00:00').toLocaleDateString('pt-BR')}
                                                </td>

                                                <td className="py-3 px-4 text-right font-medium text-emerald-400">
                                                    {parseFloat(day.realized_inflow) > 0 ? formatBRL(day.realized_inflow) : '-'}
                                                </td>

                                                <td className="py-3 px-4 text-right font-medium text-rose-400">
                                                    {parseFloat(day.realized_outflow) > 0 ? formatBRL(day.realized_outflow) : '-'}
                                                </td>

                                                <td className="py-3 px-4 text-right font-medium text-blue-400">
                                                    {parseFloat(day.projected_inflow) > 0 ? formatBRL(day.projected_inflow) : '-'}
                                                </td>

                                                <td className="py-3 px-4 text-right font-medium text-amber-400">
                                                    {parseFloat(day.projected_outflow) > 0 ? formatBRL(day.projected_outflow) : '-'}
                                                </td>

                                                <td className={`py-3 px-4 text-right font-bold ${parseFloat(day.balance) >= 0 ? 'text-white' : 'text-rose-400'}`}>
                                                    {formatBRL(day.balance)}
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </TenantDesktopLayout>
    );
}
