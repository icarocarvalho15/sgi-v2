import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import { 
    DollarSign, 
    TrendingUp, 
    ArrowUpRight, 
    ArrowDownLeft, 
    Wallet, 
    FileText, 
    Sliders, 
    Boxes, 
    AlertTriangle, 
    CheckCircle2, 
    Clock, 
    PlusCircle, 
    Users, 
    Layers, 
    Printer,
    ChevronRight,
    ArrowRight
} from 'lucide-react';

interface BankAccount {
    id: string;
    name: string;
    bank_name?: string;
    current_balance: string;
}

interface MonthHistory {
    month: string;
    inflow: number;
    outflow: number;
    net: number;
}

interface StatusCount {
    status: string;
    count: number;
    total?: string;
}

interface RecentQuote {
    id: string;
    quote_number: string;
    customer?: { id: string; name: string };
    partner?: { id: string; name: string };
    title: string;
    status: string;
    total_amount: string;
    created_at: string;
}

interface RecentOrder {
    id: string;
    order_number: string;
    bom?: { id: string; name: string };
    product_name?: string;
    target_quantity?: number;
    produced_quantity?: number;
    status: string;
    due_date?: string | null;
    created_at: string;
}

interface LowStockItem {
    id: string;
    quantity_available?: string;
    quantity_on_hand?: string;
    item: {
        id: string;
        code: string;
        name: string;
        unit_of_measure: string;
    };
}

interface Props {
    tenant: {
        id: string;
        name: string;
    };
    kpis: {
        financial: {
            revenue_month: string;
            received_month: string;
            payable_month: string;
            paid_month: string;
            pending_receivable: string;
            pending_payable: string;
            total_bank_balance: string;
            bank_accounts: BankAccount[];
            history: MonthHistory[];
        };
        sales: {
            quotes_count: number;
            quotes_value: string;
            approved_count: number;
            approved_value: string;
            conversion_rate: number;
            status_distribution: StatusCount[];
            recent_quotes: RecentQuote[];
        };
        production: {
            active_orders: number;
            completed_month: number;
            status_distribution: StatusCount[];
            recent_orders: RecentOrder[];
        };
        inventory: {
            total_items: number;
            total_stock_value: string;
            low_stock_items: LowStockItem[];
        };
    };
}

export default function TenantDashboard({ tenant, kpis }: Props) {
    const formatCurrency = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num || 0);
    };

    const maxHistoryValue = Math.max(
        ...kpis.financial.history.map((h) => Math.max(h.inflow, h.outflow)),
        1000
    );

    return (
        <AppLayout title="Painel Operacional & Executivo">
            <Head title={`Dashboard - ${tenant.name}`} />

            <div className="space-y-6">
                {/* Header com Boas-vindas e Ações Rápidas */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800/80 rounded-2xl p-6 shadow-xl">
                    <div>
                        <span className="text-xs font-semibold uppercase tracking-wider text-indigo-400 block mb-1">
                            Visão Geral da Gráfica & Brindes
                        </span>
                        <h1 className="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                            {tenant.name}
                        </h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Acompanhamento em tempo real de vendas, faturamento, produção (PCP) e estoques.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        <Link
                            href="/quotes?create=true"
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition-all shadow-md shadow-indigo-600/20"
                        >
                            <FileText className="w-3.5 h-3.5" />
                            Novo Orçamento
                        </Link>
                        <Link
                            href="/production/orders"
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 rounded-xl text-xs font-semibold transition-colors"
                        >
                            <Sliders className="w-3.5 h-3.5 text-amber-400" />
                            Chão de Fábrica
                        </Link>
                        <Link
                            href="/finance/entries/create"
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 rounded-xl text-xs font-semibold transition-colors"
                        >
                            <DollarSign className="w-3.5 h-3.5 text-emerald-400" />
                            Lançamento Financeiro
                        </Link>
                        <Link
                            href="/crm/partners/create"
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 rounded-xl text-xs font-semibold transition-colors"
                        >
                            <Users className="w-3.5 h-3.5 text-sky-400" />
                            Novo Cliente
                        </Link>
                    </div>
                </div>

                {/* Grid de KPIs Principais */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Card 1: Faturamento do Mês */}
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 hover:border-slate-700 transition-all">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-medium text-slate-400">Receitas no Mês</span>
                            <div className="w-9 h-9 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                                <ArrowDownLeft className="w-4 h-4" />
                            </div>
                        </div>
                        <div className="mt-3">
                            <div className="text-2xl font-bold text-white tracking-tight">
                                {formatCurrency(kpis.financial.revenue_month)}
                            </div>
                            <div className="flex items-center justify-between mt-2 text-xs">
                                <span className="text-emerald-400 font-medium">
                                    {formatCurrency(kpis.financial.received_month)} recebido
                                </span>
                                <span className="text-slate-500">
                                    {formatCurrency(kpis.financial.pending_receivable)} a receber
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Card 2: Contas a Pagar do Mês */}
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 hover:border-slate-700 transition-all">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-medium text-slate-400">Despesas no Mês</span>
                            <div className="w-9 h-9 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-400">
                                <ArrowUpRight className="w-4 h-4" />
                            </div>
                        </div>
                        <div className="mt-3">
                            <div className="text-2xl font-bold text-white tracking-tight">
                                {formatCurrency(kpis.financial.payable_month)}
                            </div>
                            <div className="flex items-center justify-between mt-2 text-xs">
                                <span className="text-rose-400 font-medium">
                                    {formatCurrency(kpis.financial.paid_month)} pago
                                </span>
                                <span className="text-slate-500">
                                    {formatCurrency(kpis.financial.pending_payable)} pendente
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Card 3: Orçamentos & Taxa de Conversão */}
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 hover:border-slate-700 transition-all">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-medium text-slate-400">Funil de Vendas</span>
                            <div className="w-9 h-9 rounded-xl bg-sky-500/10 flex items-center justify-center text-sky-400">
                                <FileText className="w-4 h-4" />
                            </div>
                        </div>
                        <div className="mt-3">
                            <div className="flex items-baseline justify-between">
                                <div className="text-2xl font-bold text-white tracking-tight">
                                    {kpis.sales.quotes_count} orçamentos
                                </div>
                                <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-sky-500/10 text-sky-300 border border-sky-500/20">
                                    {kpis.sales.conversion_rate}% conv.
                                </span>
                            </div>
                            <div className="flex items-center justify-between mt-2 text-xs">
                                <span className="text-emerald-400 font-medium">
                                    {formatCurrency(kpis.sales.approved_value)} aprovados
                                </span>
                                <span className="text-slate-500">
                                    {formatCurrency(kpis.sales.quotes_value)} total cotado
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Card 4: Produção Ativa & Saldo em Bancos */}
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 hover:border-slate-700 transition-all">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-medium text-slate-400">PCP & Saldo Bancário</span>
                            <div className="w-9 h-9 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                                <Wallet className="w-4 h-4" />
                            </div>
                        </div>
                        <div className="mt-3">
                            <div className="text-2xl font-bold text-white tracking-tight">
                                {formatCurrency(kpis.financial.total_bank_balance)}
                            </div>
                            <div className="flex items-center justify-between mt-2 text-xs">
                                <span className="text-amber-400 font-medium">
                                    {kpis.production.active_orders} OPs em execução
                                </span>
                                <span className="text-emerald-400">
                                    {kpis.production.completed_month} entregues no mês
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Seção Central: Gráfico de Evolução Financeira & Distribuição do Chão de Fábrica */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Gráfico Comparativo 6 Meses */}
                    <div className="lg:col-span-2 bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6">
                        <div className="flex items-center justify-between mb-6">
                            <div>
                                <h3 className="text-base font-semibold text-white tracking-tight flex items-center gap-2">
                                    <TrendingUp className="w-4 h-4 text-indigo-400" />
                                    Fluxo de Caixa Realizado (Últimos 6 Meses)
                                </h3>
                                <p className="text-xs text-slate-400 mt-0.5">
                                    Comparativo de recebimentos e pagamentos efetivados.
                                </p>
                            </div>
                            <div className="flex items-center gap-4 text-xs">
                                <span className="flex items-center gap-1.5 text-slate-300">
                                    <span className="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                                    Receitas
                                </span>
                                <span className="flex items-center gap-1.5 text-slate-300">
                                    <span className="w-3 h-3 rounded-full bg-rose-500 inline-block"></span>
                                    Despesas
                                </span>
                            </div>
                        </div>

                        {/* Barras do Gráfico */}
                        <div className="space-y-4 pt-2">
                            {kpis.financial.history.map((m, idx) => {
                                const inflowPct = Math.min(100, Math.round((m.inflow / maxHistoryValue) * 100));
                                const outflowPct = Math.min(100, Math.round((m.outflow / maxHistoryValue) * 100));

                                return (
                                    <div key={idx} className="space-y-1.5">
                                        <div className="flex items-center justify-between text-xs">
                                            <span className="font-medium text-slate-300 w-16">{m.month}</span>
                                            <div className="flex items-center gap-3">
                                                <span className="text-emerald-400 font-mono">
                                                    +{formatCurrency(m.inflow)}
                                                </span>
                                                <span className="text-slate-600">/</span>
                                                <span className="text-rose-400 font-mono">
                                                    -{formatCurrency(m.outflow)}
                                                </span>
                                                <span className={`text-[11px] font-mono font-semibold px-2 py-0.5 rounded ${
                                                    m.net >= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'
                                                }`}>
                                                    Líq: {formatCurrency(m.net)}
                                                </span>
                                            </div>
                                        </div>
                                        <div className="w-full bg-slate-950 rounded-full h-3 flex overflow-hidden border border-slate-800">
                                            <div
                                                style={{ width: `${inflowPct}%` }}
                                                className="bg-emerald-500 h-full rounded-l-full transition-all duration-500"
                                                title={`Receitas: ${formatCurrency(m.inflow)}`}
                                            />
                                            <div
                                                style={{ width: `${outflowPct}%` }}
                                                className="bg-rose-500 h-full rounded-r-full transition-all duration-500 opacity-90"
                                                title={`Despesas: ${formatCurrency(m.outflow)}`}
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Card Lateral: Almoxarifado, Kardex e Estoque Crítico */}
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between pb-4 border-b border-slate-800">
                                <div>
                                    <h3 className="text-base font-semibold text-white tracking-tight flex items-center gap-2">
                                        <Boxes className="w-4 h-4 text-indigo-400" />
                                        Patrimônio em Estoque
                                    </h3>
                                    <p className="text-xs text-slate-400 mt-0.5">
                                        Valoração contábil via Kardex (WAC)
                                    </p>
                                </div>
                                <Link
                                    href="/inventory/balances"
                                    className="text-xs text-indigo-400 hover:text-indigo-300 font-medium flex items-center gap-1"
                                >
                                    Ver tudo <ArrowRight className="w-3 h-3" />
                                </Link>
                            </div>

                            <div className="my-5 p-4 rounded-xl bg-slate-950/80 border border-slate-800 flex items-center justify-between">
                                <div>
                                    <span className="text-[11px] uppercase tracking-wider text-slate-400 block">
                                        Valor Total Almoxarifado
                                    </span>
                                    <span className="text-xl font-bold text-emerald-400 font-mono mt-0.5 block">
                                        {formatCurrency(kpis.inventory.total_stock_value)}
                                    </span>
                                </div>
                                <div className="text-right">
                                    <span className="text-[11px] uppercase tracking-wider text-slate-400 block">
                                        Insumos Ativos
                                    </span>
                                    <span className="text-lg font-bold text-white mt-0.5 block">
                                        {kpis.inventory.total_items} itens
                                    </span>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <span className="text-xs font-semibold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                                    <AlertTriangle className="w-3.5 h-3.5" />
                                    Alertas de Reposição / Estoque Baixo
                                </span>

                                {kpis.inventory.low_stock_items.length === 0 ? (
                                    <div className="p-3 bg-emerald-500/5 border border-emerald-500/20 rounded-xl text-xs text-emerald-400 flex items-center gap-2">
                                        <CheckCircle2 className="w-4 h-4 shrink-0" />
                                        Todos os insumos possuem saldo regular no almoxarifado.
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {kpis.inventory.low_stock_items.map((b) => (
                                            <div
                                                key={b.id}
                                                className="p-2.5 bg-slate-950 border border-slate-800 rounded-xl flex items-center justify-between text-xs"
                                            >
                                                <div className="truncate pr-2">
                                                    <span className="font-semibold text-slate-200 block truncate">
                                                        {b.item.name}
                                                    </span>
                                                    <span className="text-[10px] text-slate-500 font-mono">
                                                        Cód: {b.item.code}
                                                    </span>
                                                </div>
                                                <span className="px-2 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20 font-bold shrink-0">
                                                    {b.quantity_available || b.quantity_on_hand} {b.item.unit_of_measure}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="pt-4 mt-6 border-t border-slate-800">
                            <Link
                                href="/finance?tab=kardex"
                                className="w-full flex items-center justify-center gap-2 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition-colors"
                            >
                                Consultar Extrato Razão Kardex
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Duas Colunas de Atividades Recentes: Orçamentos & Chão de Fábrica */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Últimos Orçamentos */}
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-800">
                            <div>
                                <h3 className="text-base font-semibold text-white tracking-tight flex items-center gap-2">
                                    <FileText className="w-4 h-4 text-indigo-400" />
                                    Últimos Orçamentos Emitidos
                                </h3>
                                <p className="text-xs text-slate-400 mt-0.5">
                                    Cotações recentes e propostas no funil comercial.
                                </p>
                            </div>
                            <Link
                                href="/quotes"
                                className="text-xs text-indigo-400 hover:text-indigo-300 font-medium flex items-center gap-1"
                            >
                                Ver todos <ArrowRight className="w-3 h-3" />
                            </Link>
                        </div>

                        <div className="divide-y divide-slate-800/60 mt-3">
                            {kpis.sales.recent_quotes.length === 0 ? (
                                <p className="text-xs text-slate-500 py-6 text-center">Nenhum orçamento emitido ainda.</p>
                            ) : (
                                kpis.sales.recent_quotes.map((q) => (
                                    <div key={q.id} className="py-3 flex items-center justify-between gap-3 text-xs">
                                        <div className="truncate">
                                            <div className="flex items-center gap-2">
                                                <span className="font-mono font-bold text-slate-200">
                                                    {q.quote_number}
                                                </span>
                                                <span className="font-medium text-slate-300 truncate">
                                                    {q.title}
                                                </span>
                                            </div>
                                            <span className="text-[11px] text-slate-500 block mt-0.5 truncate">
                                                {q.customer?.name || q.partner?.name || 'Cliente Geral'}
                                            </span>
                                        </div>
                                        <div className="text-right shrink-0">
                                            <span className="font-bold text-emerald-400 font-mono block">
                                                {formatCurrency(q.total_amount)}
                                            </span>
                                            <span className="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-800 text-slate-300 border border-slate-700">
                                                {q.status}
                                            </span>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>

                    {/* Últimas Ordens de Produção (PCP) */}
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-800">
                            <div>
                                <h3 className="text-base font-semibold text-white tracking-tight flex items-center gap-2">
                                    <Sliders className="w-4 h-4 text-indigo-400" />
                                    Chão de Fábrica & OPs em Andamento
                                </h3>
                                <p className="text-xs text-slate-400 mt-0.5">
                                    Ordens de produção gráficas e acompanhamento de etapas.
                                </p>
                            </div>
                            <Link
                                href="/production/orders"
                                className="text-xs text-indigo-400 hover:text-indigo-300 font-medium flex items-center gap-1"
                            >
                                Ver PCP <ArrowRight className="w-3 h-3" />
                            </Link>
                        </div>

                        <div className="divide-y divide-slate-800/60 mt-3">
                            {kpis.production.recent_orders.length === 0 ? (
                                <p className="text-xs text-slate-500 py-6 text-center">Nenhuma ordem de produção em execução.</p>
                            ) : (
                                kpis.production.recent_orders.map((o) => (
                                    <div key={o.id} className="py-3 flex items-center justify-between gap-3 text-xs">
                                        <div className="truncate">
                                            <div className="flex items-center gap-2">
                                                <span className="font-mono font-bold text-indigo-300">
                                                    {o.order_number}
                                                </span>
                                                <span className="font-medium text-slate-300 truncate">
                                                    {o.bom?.name || 'Item Gráfico Personalizado'}
                                                </span>
                                            </div>
                                            <span className="text-[11px] text-slate-500 block mt-0.5">
                                                Qtd: {o.produced_quantity || 0}/{o.target_quantity || 0} un.
                                                {o.due_date && ` • Entrega: ${o.due_date}`}
                                            </span>
                                        </div>
                                        <div className="text-right shrink-0">
                                            <span className="inline-block px-2 py-0.5 rounded text-[10px] font-semibold border bg-slate-800 text-slate-300 border-slate-700">
                                                {o.status}
                                            </span>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
