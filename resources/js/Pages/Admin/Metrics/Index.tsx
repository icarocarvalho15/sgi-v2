import React from 'react';
import { Head } from '@inertiajs/react';
import LandlordLayout from '@/Layouts/LandlordLayout';
import { 
    TrendingUp, 
    DollarSign, 
    Building2, 
    Users, 
    Calendar,
    ArrowUpRight,
    PieChart,
    Layers,
    ShieldCheck
} from 'lucide-react';

interface PlanDistribution {
    name: string;
    count: number;
    revenue: number;
}

interface Metrics {
    mrr: number;
    arr: number;
    totalTenants: number;
    activeTenants: number;
    suspendedTenants: number;
    totalUsers: number;
    planDistribution: PlanDistribution[];
}

interface Props {
    metrics: Metrics;
}

export default function MetricsIndex({ metrics }: Props) {
    return (
        <LandlordLayout title="Métricas SaaS & Faturamento">
            <Head title="Landlord - Métricas do Negócio" />

            <div className="space-y-8">
                {/* Header */}
                <div className="bg-slate-900 p-6 rounded-2xl border border-slate-800 flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center gap-1">
                                <TrendingUp className="w-3.5 h-3.5" /> Performance Financeira SaaS
                            </span>
                        </div>
                        <h1 className="text-2xl font-bold text-white tracking-tight">Métricas Globais de Faturamento</h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Acompanhamento de Receita Recorrente Mensal (MRR), Anual (ARR) e densidade de assinantes.
                        </p>
                    </div>
                </div>

                {/* Big Metric Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                        <div className="flex items-center justify-between text-slate-400">
                            <span className="text-xs font-bold uppercase tracking-wider">MRR (Mensal)</span>
                            <DollarSign className="w-5 h-5 text-emerald-400" />
                        </div>
                        <div className="text-3xl font-extrabold text-emerald-400">
                            R$ {Number(metrics.mrr).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                        </div>
                        <span className="text-xs text-slate-500 flex items-center gap-1">
                            <ArrowUpRight className="w-3.5 h-3.5 text-emerald-400" />
                            Receita Recorrente Ativa
                        </span>
                    </div>

                    <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                        <div className="flex items-center justify-between text-slate-400">
                            <span className="text-xs font-bold uppercase tracking-wider">ARR (Anual Projetado)</span>
                            <TrendingUp className="w-5 h-5 text-indigo-400" />
                        </div>
                        <div className="text-3xl font-extrabold text-white">
                            R$ {Number(metrics.arr).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                        </div>
                        <span className="text-xs text-slate-500">
                            Base de cálculo: 12 x MRR
                        </span>
                    </div>

                    <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                        <div className="flex items-center justify-between text-slate-400">
                            <span className="text-xs font-bold uppercase tracking-wider">Empresas Ativas</span>
                            <Building2 className="w-5 h-5 text-amber-400" />
                        </div>
                        <div className="text-3xl font-extrabold text-white">
                            {metrics.activeTenants}
                            <span className="text-sm font-normal text-slate-400 ml-1">/ {metrics.totalTenants}</span>
                        </div>
                        <span className="text-xs text-emerald-400">
                            {metrics.totalTenants > 0 ? ((metrics.activeTenants / metrics.totalTenants) * 100).toFixed(0) : 0}% de taxa de ativação
                        </span>
                    </div>

                    <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                        <div className="flex items-center justify-between text-slate-400">
                            <span className="text-xs font-bold uppercase tracking-wider">Usuários no ERP</span>
                            <Users className="w-5 h-5 text-sky-400" />
                        </div>
                        <div className="text-3xl font-extrabold text-white">
                            {metrics.totalUsers}
                        </div>
                        <span className="text-xs text-slate-500">
                            Operadores e gestores gráficos
                        </span>
                    </div>
                </div>

                {/* Distribuição por Plano */}
                <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-5">
                    <div className="flex items-center justify-between border-b border-slate-800 pb-4">
                        <h2 className="font-bold text-white text-base flex items-center gap-2">
                            <PieChart className="w-5 h-5 text-amber-400" />
                            Receita & Assinantes por Tier de Plano
                        </h2>
                        <span className="text-xs text-slate-400">Composição do MRR</span>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                        {metrics.planDistribution.map((item, idx) => (
                            <div
                                key={idx}
                                className="bg-slate-950/60 p-5 rounded-xl border border-slate-800 space-y-3"
                            >
                                <div className="flex items-center justify-between">
                                    <h3 className="font-bold text-white text-sm">{item.name}</h3>
                                    <span className="px-2 py-0.5 rounded text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        {item.count} {item.count === 1 ? 'cliente' : 'clientes'}
                                    </span>
                                </div>

                                <div className="pt-2 border-t border-slate-800 flex items-baseline justify-between">
                                    <span className="text-xs text-slate-400 font-medium">Receita Mensal:</span>
                                    <span className="text-lg font-extrabold text-emerald-400 font-mono">
                                        R$ {Number(item.revenue).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </LandlordLayout>
    );
}
