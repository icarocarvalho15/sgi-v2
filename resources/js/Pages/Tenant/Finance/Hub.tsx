import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import { 
    DollarSign, 
    TrendingUp, 
    FileSpreadsheet, 
    ArrowDownLeft, 
    ArrowUpRight, 
    PlusCircle, 
    Search, 
    Filter, 
    CheckCircle2, 
    Clock, 
    Building2, 
    X,
    Wallet,
    Calendar,
    Boxes,
    Download,
    ShieldCheck,
    Tag,
    ChevronRight,
    ArrowRight
} from 'lucide-react';
import { 
    exportFinancialEntriesToCsv, 
    exportCashFlowAndDreToCsv, 
    exportKardexToCsv 
} from '../../../Utils/exportToCsv';

interface Partner {
    id: string;
    name: string;
    document?: string;
}

interface BankAccount {
    id: string;
    name: string;
    bank_name?: string;
    current_balance: string;
}

interface FinancialEntry {
    id: string;
    type: 'receivable' | 'payable';
    entry_number: string;
    description: string;
    original_amount: string;
    discount_amount: string;
    interest_fine_amount: string;
    net_amount: string;
    paid_amount: string;
    balance_due: string;
    due_date: string;
    payment_date: string | null;
    status: 'pending' | 'partial' | 'paid' | 'cancelled';
    installment_number: number;
    total_installments: number;
    partner?: Partner;
    bankAccount?: BankAccount;
}

interface ItemOption {
    id: string;
    name: string;
    code: string;
    unit_of_measure: string;
}

interface WarehouseOption {
    id: string;
    name: string;
    code: string;
}

interface KardexMovement {
    id: string;
    movement_type: string;
    direction: 'in' | 'out';
    quantity: string;
    unit_cost: string;
    total_cost: string;
    balance_quantity_after: string;
    balance_average_cost_after: string;
    balance_total_value_after: string;
    reference_type: string | null;
    reference_id: string | null;
    notes: string | null;
    created_at: string;
    item: {
        id: string;
        name: string;
        code: string;
        unit_of_measure: string;
    };
    warehouse: {
        id: string;
        name: string;
        code: string;
    };
}

interface Props {
    activeTab?: 'entries' | 'reports' | 'kardex';
    entries: {
        data: FinancialEntry[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    bankAccounts: BankAccount[];
    metrics: {
        total_receivable: string;
        total_payable: string;
    };
    entryFilters: {
        type?: string;
        status?: string;
        search?: string;
    };
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
    reportFilters: {
        start_date: string;
        end_date: string;
    };
    movements: {
        data: KardexMovement[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    items: ItemOption[];
    warehouses: WarehouseOption[];
    selectedItem: ItemOption | null;
    kardexFilters: {
        item_id?: string;
        warehouse_id?: string;
        movement_type?: string;
    };
}

export default function FinancialHub({
    activeTab: initialTab = 'entries',
    entries,
    bankAccounts,
    metrics,
    entryFilters,
    cashFlow,
    dre,
    reportFilters,
    movements,
    items,
    warehouses,
    selectedItem,
    kardexFilters
}: Props) {
    const [currentTab, setCurrentTab] = useState<'entries' | 'reports' | 'kardex'>(initialTab);

    // Filtros de Títulos
    const [search, setSearch] = useState(entryFilters.search || '');
    const [type, setType] = useState(entryFilters.type || '');
    const [status, setStatus] = useState(entryFilters.status || '');

    // Filtros de Relatórios
    const [startDate, setStartDate] = useState(reportFilters.start_date);
    const [endDate, setEndDate] = useState(reportFilters.end_date);

    // Filtros do Kardex
    const [kardexItemId, setKardexItemId] = useState(kardexFilters.item_id || '');
    const [kardexWarehouseId, setKardexWarehouseId] = useState(kardexFilters.warehouse_id || '');
    const [kardexMovementType, setKardexMovementType] = useState(kardexFilters.movement_type || '');

    // Modal de Baixa de Título
    const [selectedEntry, setSelectedEntry] = useState<FinancialEntry | null>(null);
    const paymentForm = useForm({
        financial_entry_id: '',
        bank_account_id: bankAccounts[0]?.id || '',
        payment_method: 'pix',
        amount: '',
        discount_amount: '0.00',
        interest_fine_amount: '0.00',
        payment_date: new Date().toISOString().split('T')[0],
        notes: '',
    });

    const formatCurrency = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num || 0);
    };

    const formatDate = (dateStr?: string | null) => {
        if (!dateStr) return '-';
        const [year, month, day] = dateStr.split('T')[0].split('-');
        return `${day}/${month}/${year}`;
    };

    // Submissão de Filtros
    const handleEntryFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/finance', {
            tab: 'entries',
            search: search || undefined,
            type: type || undefined,
            status: status || undefined,
        }, { preserveState: true });
    };

    const handleReportFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/finance', {
            tab: 'reports',
            start_date: startDate,
            end_date: endDate,
        }, { preserveState: true });
    };

    const handleKardexFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/finance', {
            tab: 'kardex',
            item_id: kardexItemId || undefined,
            warehouse_id: kardexWarehouseId || undefined,
            movement_type: kardexMovementType || undefined,
        }, { preserveState: true });
    };

    // Baixa de Título
    const openPaymentModal = (entry: FinancialEntry) => {
        setSelectedEntry(entry);
        paymentForm.setData({
            financial_entry_id: entry.id,
            bank_account_id: bankAccounts[0]?.id || '',
            payment_method: 'pix',
            amount: entry.balance_due,
            discount_amount: '0.00',
            interest_fine_amount: '0.00',
            payment_date: new Date().toISOString().split('T')[0],
            notes: '',
        });
    };

    const handlePaymentSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        paymentForm.post('/finance/payments', {
            onSuccess: () => {
                setSelectedEntry(null);
                paymentForm.reset();
            },
        });
    };

    // Ação de Exportação Dinâmica conforme a Aba Ativa
    const handleExportCurrentTab = () => {
        if (currentTab === 'entries') {
            exportFinancialEntriesToCsv(entries.data);
        } else if (currentTab === 'reports') {
            exportCashFlowAndDreToCsv(cashFlow, dre);
        } else if (currentTab === 'kardex') {
            exportKardexToCsv(movements.data);
        }
    };

    return (
        <AppLayout title="Financeiro & Controladoria">
            <Head title="Financeiro & Controladoria - SGI v2.0" />

            <div className="space-y-6">
                {/* Header Superior com Título, Exportador de Planilhas e Novo Título */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <span className="text-xs font-semibold uppercase tracking-wider text-indigo-400 block mb-1">
                            Controladoria Geral da Gráfica
                        </span>
                        <h1 className="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                            <DollarSign className="w-7 h-7 text-indigo-400" />
                            Financeiro & Controladoria
                        </h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Gestão unificada de contas a pagar/receber, conciliação de caixa, DRE e apuração de custos Kardex.
                        </p>
                    </div>

                    <div className="flex items-center gap-2.5">
                        <button
                            type="button"
                            onClick={handleExportCurrentTab}
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 rounded-xl text-xs font-semibold transition-colors shadow-sm"
                            title="Exportar dados da aba atual em formato compatível com Excel e Google Sheets"
                        >
                            <Download className="w-3.5 h-3.5 text-indigo-400" />
                            Exportar Planilha (CSV)
                        </button>

                        <Link
                            href="/finance/entries/create"
                            className="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition-all shadow-md shadow-indigo-600/20"
                        >
                            <PlusCircle className="w-3.5 h-3.5" />
                            Novo Lançamento
                        </Link>
                    </div>
                </div>

                {/* Seletor de Abas Unificadas */}
                <div className="flex border-b border-slate-800 space-x-2">
                    <button
                        type="button"
                        onClick={() => {
                            setCurrentTab('entries');
                            router.get('/finance', { tab: 'entries' }, { preserveState: true });
                        }}
                        className={`flex items-center gap-2 px-4 py-3 text-xs font-semibold border-b-2 transition-all ${
                            currentTab === 'entries'
                                ? 'border-indigo-500 text-indigo-400 bg-indigo-500/10 rounded-t-xl'
                                : 'border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 rounded-t-xl'
                        }`}
                    >
                        <DollarSign className="w-4 h-4" />
                        Títulos (Contas a Pagar & Receber)
                        <span className="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-300 font-mono">
                            {entries.total}
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            setCurrentTab('reports');
                            router.get('/finance', { tab: 'reports' }, { preserveState: true });
                        }}
                        className={`flex items-center gap-2 px-4 py-3 text-xs font-semibold border-b-2 transition-all ${
                            currentTab === 'reports'
                                ? 'border-indigo-500 text-indigo-400 bg-indigo-500/10 rounded-t-xl'
                                : 'border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 rounded-t-xl'
                        }`}
                    >
                        <TrendingUp className="w-4 h-4" />
                        Fluxo de Caixa & DRE Gerencial
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            setCurrentTab('kardex');
                            router.get('/finance', { tab: 'kardex' }, { preserveState: true });
                        }}
                        className={`flex items-center gap-2 px-4 py-3 text-xs font-semibold border-b-2 transition-all ${
                            currentTab === 'kardex'
                                ? 'border-indigo-500 text-indigo-400 bg-indigo-500/10 rounded-t-xl'
                                : 'border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 rounded-t-xl'
                        }`}
                    >
                        <FileSpreadsheet className="w-4 h-4" />
                        Razão Kardex Contábil (Insumos)
                        <span className="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-300 font-mono">
                            {movements.total}
                        </span>
                    </button>
                </div>

                {/* ========================================================================= */}
                {/* ABA 1: TÍTULOS (CONTAS A PAGAR & RECEBER)                                 */}
                {/* ========================================================================= */}
                {currentTab === 'entries' && (
                    <div className="space-y-6">
                        {/* Métricas do Painel */}
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-medium text-slate-400">Total a Receber (Pendente)</span>
                                    <div className="w-8 h-8 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                                        <ArrowDownLeft className="w-4 h-4" />
                                    </div>
                                </div>
                                <div className="text-2xl font-bold text-emerald-400 mt-2 font-mono">
                                    {formatCurrency(metrics.total_receivable)}
                                </div>
                            </div>

                            <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-medium text-slate-400">Total a Pagar (Pendente)</span>
                                    <div className="w-8 h-8 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-400">
                                        <ArrowUpRight className="w-4 h-4" />
                                    </div>
                                </div>
                                <div className="text-2xl font-bold text-rose-400 mt-2 font-mono">
                                    {formatCurrency(metrics.total_payable)}
                                </div>
                            </div>

                            <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-medium text-slate-400">Saldo Bancário Consolidado</span>
                                    <div className="w-8 h-8 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                                        <Wallet className="w-4 h-4" />
                                    </div>
                                </div>
                                <div className="text-2xl font-bold text-indigo-300 mt-2 font-mono">
                                    {formatCurrency(bankAccounts.reduce((acc, b) => acc + parseFloat(b.current_balance || '0'), 0))}
                                </div>
                            </div>
                        </div>

                        {/* Filtros de Busca */}
                        <form onSubmit={handleEntryFilter} className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 flex flex-col md:flex-row items-center gap-3">
                            <div className="flex-1 relative w-full">
                                <Search className="w-4 h-4 text-slate-500 absolute left-3 top-3" />
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Buscar por descrição ou número do título..."
                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <select
                                value={type}
                                onChange={(e) => setType(e.target.value)}
                                className="w-full md:w-44 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Todos os Tipos</option>
                                <option value="receivable">A Receber</option>
                                <option value="payable">A Pagar</option>
                            </select>

                            <select
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                className="w-full md:w-44 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Todos os Status</option>
                                <option value="pending">Pendente</option>
                                <option value="partial">Parcialmente Pago</option>
                                <option value="paid">Totalmente Pago</option>
                                <option value="cancelled">Cancelado</option>
                            </select>

                            <button
                                type="submit"
                                className="w-full md:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition-colors"
                            >
                                Filtrar
                            </button>
                        </form>

                        {/* Tabela de Títulos */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr className="border-b border-slate-800 bg-slate-950/40 text-slate-400 font-semibold uppercase tracking-wider">
                                            <th className="py-3 px-4">Número</th>
                                            <th className="py-3 px-4">Tipo</th>
                                            <th className="py-3 px-4">Descrição</th>
                                            <th className="py-3 px-4">Parceiro</th>
                                            <th className="py-3 px-4">Vencimento</th>
                                            <th className="py-3 px-4">Valor Original</th>
                                            <th className="py-3 px-4">Saldo Devedor</th>
                                            <th className="py-3 px-4">Status</th>
                                            <th className="py-3 px-4 text-right">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-800/60 text-slate-300">
                                        {entries.data.length === 0 ? (
                                            <tr>
                                                <td colSpan={9} className="py-8 text-center text-slate-500">
                                                    Nenhum título financeiro encontrado.
                                                </td>
                                            </tr>
                                        ) : (
                                            entries.data.map((entry) => (
                                                <tr key={entry.id} className="hover:bg-slate-800/30 transition-colors">
                                                    <td className="py-3 px-4 font-mono font-medium text-slate-200">
                                                        {entry.entry_number}
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold ${
                                                            entry.type === 'receivable'
                                                                ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                                                                : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'
                                                        }`}>
                                                            {entry.type === 'receivable' ? 'Receber' : 'Pagar'}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-4 font-medium text-white max-w-xs truncate">
                                                        {entry.description}
                                                        {entry.total_installments > 1 && (
                                                            <span className="text-[10px] text-slate-500 ml-1.5 font-mono">
                                                                ({entry.installment_number}/{entry.total_installments})
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="py-3 px-4 text-slate-400 max-w-xs truncate">
                                                        {entry.partner?.name || '-'}
                                                    </td>
                                                    <td className="py-3 px-4 font-mono">
                                                        {formatDate(entry.due_date)}
                                                    </td>
                                                    <td className="py-3 px-4 font-mono">
                                                        {formatCurrency(entry.original_amount)}
                                                    </td>
                                                    <td className="py-3 px-4 font-mono font-bold text-white">
                                                        {formatCurrency(entry.balance_due)}
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        <span className={`inline-block px-2 py-0.5 rounded text-[10px] font-semibold border ${
                                                            entry.status === 'paid'
                                                                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                                                                : entry.status === 'partial'
                                                                ? 'bg-amber-500/10 text-amber-400 border-amber-500/20'
                                                                : entry.status === 'cancelled'
                                                                ? 'bg-slate-800 text-slate-400 border-slate-700'
                                                                : 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20'
                                                        }`}>
                                                            {entry.status === 'paid' ? 'Liquidado' :
                                                             entry.status === 'partial' ? 'Baixa Parcial' :
                                                             entry.status === 'cancelled' ? 'Cancelado' : 'Aberto'}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-4 text-right">
                                                        {entry.status !== 'paid' && entry.status !== 'cancelled' && (
                                                            <button
                                                                type="button"
                                                                onClick={() => openPaymentModal(entry)}
                                                                className="px-2.5 py-1 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 rounded-lg text-xs font-semibold transition-colors"
                                                            >
                                                                Baixar
                                                            </button>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Paginação */}
                            {entries.links && entries.links.length > 3 && (
                                <div className="p-4 border-t border-slate-800 flex items-center justify-between">
                                    <span className="text-xs text-slate-400">
                                        Mostrando {entries.data.length} de {entries.total} títulos
                                    </span>
                                    <div className="flex items-center gap-1">
                                        {entries.links.map((link, idx) => (
                                            <button
                                                key={idx}
                                                disabled={!link.url}
                                                onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className={`px-3 py-1 rounded-lg text-xs font-medium transition-colors ${
                                                    link.active
                                                        ? 'bg-indigo-600 text-white'
                                                        : link.url
                                                        ? 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                                                        : 'bg-transparent text-slate-600 cursor-not-allowed'
                                                }`}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* ========================================================================= */}
                {/* ABA 2: FLUXO DE CAIXA & DRE GERENCIAL                                     */}
                {/* ========================================================================= */}
                {currentTab === 'reports' && (
                    <div className="space-y-6">
                        {/* Filtro por Período */}
                        <form onSubmit={handleReportFilter} className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4">
                            <div className="flex items-center gap-2">
                                <Calendar className="w-4 h-4 text-indigo-400" />
                                <span className="text-xs font-medium text-slate-300">Competência / Período:</span>
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                    className="bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                />
                                <span className="text-slate-500 text-xs">até</span>
                                <input
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                    className="bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                />
                                <button
                                    type="submit"
                                    className="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition-colors"
                                >
                                    Atualizar Demonstrativos
                                </button>
                            </div>
                        </form>

                        {/* Comparativo de Fluxo de Caixa */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Realizado */}
                            <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6">
                                <h3 className="text-base font-semibold text-white tracking-tight flex items-center gap-2 pb-3 border-b border-slate-800">
                                    <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                                    Fluxo de Caixa Realizado (Liquidez Efetivada)
                                </h3>
                                <div className="space-y-3 mt-4 text-xs">
                                    <div className="flex items-center justify-between p-3 bg-slate-950 rounded-xl border border-slate-800/60">
                                        <span className="text-slate-400">Entradas Realizadas (Recebimentos)</span>
                                        <span className="font-bold font-mono text-emerald-400">
                                            {formatCurrency(cashFlow.realized_inflows)}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between p-3 bg-slate-950 rounded-xl border border-slate-800/60">
                                        <span className="text-slate-400">Saídas Realizadas (Pagamentos)</span>
                                        <span className="font-bold font-mono text-rose-400">
                                            {formatCurrency(cashFlow.realized_outflows)}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between p-3 bg-slate-950 rounded-xl border border-slate-700 font-semibold">
                                        <span className="text-slate-200">Saldo Líquido Efetivado</span>
                                        <span className={`font-mono text-sm ${parseFloat(cashFlow.realized_net) >= 0 ? 'text-emerald-400' : 'text-rose-400'}`}>
                                            {formatCurrency(cashFlow.realized_net)}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Projetado */}
                            <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6">
                                <h3 className="text-base font-semibold text-white tracking-tight flex items-center gap-2 pb-3 border-b border-slate-800">
                                    <Clock className="w-4 h-4 text-sky-400" />
                                    Fluxo de Caixa Projetado (Títulos em Aberto)
                                </h3>
                                <div className="space-y-3 mt-4 text-xs">
                                    <div className="flex items-center justify-between p-3 bg-slate-950 rounded-xl border border-slate-800/60">
                                        <span className="text-slate-400">Entradas Previstas a Receber</span>
                                        <span className="font-bold font-mono text-sky-400">
                                            {formatCurrency(cashFlow.projected_inflows)}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between p-3 bg-slate-950 rounded-xl border border-slate-800/60">
                                        <span className="text-slate-400">Saídas Previstas a Pagar</span>
                                        <span className="font-bold font-mono text-amber-400">
                                            {formatCurrency(cashFlow.projected_outflows)}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between p-3 bg-slate-950 rounded-xl border border-slate-700 font-semibold">
                                        <span className="text-slate-200">Saldo Líquido Previsto</span>
                                        <span className={`font-mono text-sm ${parseFloat(cashFlow.projected_net) >= 0 ? 'text-sky-400' : 'text-rose-400'}`}>
                                            {formatCurrency(cashFlow.projected_net)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* DRE Gerencial */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6">
                            <h3 className="text-base font-semibold text-white tracking-tight flex items-center gap-2 pb-4 border-b border-slate-800">
                                <TrendingUp className="w-4 h-4 text-indigo-400" />
                                Demonstrativo de Resultado do Exercício (DRE por Competência)
                            </h3>

                            <div className="mt-4 space-y-2 text-xs">
                                <div className="flex items-center justify-between p-3 bg-slate-950 rounded-xl border border-slate-800">
                                    <span className="font-medium text-slate-300">Receita Operacional Bruta</span>
                                    <span className="font-mono font-bold text-emerald-400">
                                        {formatCurrency(dre.gross_revenue)}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between p-3 bg-slate-950 rounded-xl border border-slate-800">
                                    <span className="font-medium text-slate-400">(-) Custo dos Produtos Vendidos (CMV / Insumos)</span>
                                    <span className="font-mono font-bold text-rose-400">
                                        -{formatCurrency(dre.cost_of_goods)}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between p-3 bg-slate-800/40 rounded-xl border border-slate-700 font-semibold">
                                    <span className="text-slate-200">(=) Lucro Bruto Operacional</span>
                                    <span className="font-mono text-sm text-indigo-300">
                                        {formatCurrency(dre.gross_profit)}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between p-3 bg-slate-950 rounded-xl border border-slate-800">
                                    <span className="font-medium text-slate-400">(-) Despesas Operacionais e Administrativas</span>
                                    <span className="font-mono font-bold text-rose-400">
                                        -{formatCurrency(dre.operating_expenses)}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between p-4 bg-indigo-950/20 rounded-xl border border-indigo-500/30 font-bold text-sm">
                                    <div className="flex items-center gap-3">
                                        <span className="text-white">(=) Lucro Líquido do Exercício</span>
                                        <span className="px-2 py-0.5 rounded-full text-xs bg-indigo-500/20 text-indigo-300 font-mono">
                                            Margem: {dre.margin_percentage}%
                                        </span>
                                    </div>
                                    <span className={`font-mono text-base ${parseFloat(dre.net_profit) >= 0 ? 'text-emerald-400' : 'text-rose-400'}`}>
                                        {formatCurrency(dre.net_profit)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* ========================================================================= */}
                {/* ABA 3: RAZÃO KARDEX CONTÁBIL (VALORAÇÃO DE INSUMOS)                       */}
                {/* ========================================================================= */}
                {currentTab === 'kardex' && (
                    <div className="space-y-6">
                        {/* Alerta de Auditoria Imutável */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                            <div className="flex items-center gap-2 text-slate-300">
                                <ShieldCheck className="w-4 h-4 text-emerald-400 shrink-0" />
                                <span>
                                    Livro Razão Kardex Contábil auditado: movimentações estritamente imutáveis baseadas no Custo Médio Ponderado (WAC) e Zero-Float (bcmath).
                                </span>
                            </div>
                            <Link
                                href="/inventory/adjustments/create"
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition-colors shrink-0"
                            >
                                <PlusCircle className="w-3.5 h-3.5 text-indigo-400" />
                                Novo Ajuste Manual
                            </Link>
                        </div>

                        {/* Filtros do Kardex */}
                        <form onSubmit={handleKardexFilter} className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 flex flex-col md:flex-row items-center gap-3">
                            <select
                                value={kardexItemId}
                                onChange={(e) => setKardexItemId(e.target.value)}
                                className="w-full md:flex-1 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Todos os Insumos / Substratos</option>
                                {items.map((i) => (
                                    <option key={i.id} value={i.id}>
                                        {i.code} - {i.name} ({i.unit_of_measure})
                                    </option>
                                ))}
                            </select>

                            <select
                                value={kardexWarehouseId}
                                onChange={(e) => setKardexWarehouseId(e.target.value)}
                                className="w-full md:w-56 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Todos os Almoxarifados</option>
                                {warehouses.map((w) => (
                                    <option key={w.id} value={w.id}>
                                        {w.name}
                                    </option>
                                ))}
                            </select>

                            <select
                                value={kardexMovementType}
                                onChange={(e) => setKardexMovementType(e.target.value)}
                                className="w-full md:w-52 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Todos os Tipos de Movimento</option>
                                <option value="purchase_in">Entrada por Compra</option>
                                <option value="production_out">Consumo em Produção</option>
                                <option value="adjustment_in">Ajuste Manual (+)</option>
                                <option value="adjustment_out">Ajuste Manual (-)</option>
                            </select>

                            <button
                                type="submit"
                                className="w-full md:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition-colors"
                            >
                                Filtrar
                            </button>
                        </form>

                        {/* Tabela do Kardex */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr className="border-b border-slate-800 bg-slate-950/40 text-slate-400 font-semibold uppercase tracking-wider">
                                            <th className="py-3 px-4">Data / Hora</th>
                                            <th className="py-3 px-4">Insumo</th>
                                            <th className="py-3 px-4">Almoxarifado</th>
                                            <th className="py-3 px-4">Tipo Movimento</th>
                                            <th className="py-3 px-4">Quantidade</th>
                                            <th className="py-3 px-4">Custo Unitário</th>
                                            <th className="py-3 px-4">Valor Operação</th>
                                            <th className="py-3 px-4">Saldo Físico</th>
                                            <th className="py-3 px-4">Valor Estoque</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-800/60 text-slate-300">
                                        {movements.data.length === 0 ? (
                                            <tr>
                                                <td colSpan={9} className="py-8 text-center text-slate-500">
                                                    Nenhuma movimentação registrada no Razão Kardex.
                                                </td>
                                            </tr>
                                        ) : (
                                            movements.data.map((m) => (
                                                <tr key={m.id} className="hover:bg-slate-800/30 transition-colors">
                                                    <td className="py-3 px-4 font-mono text-slate-400">
                                                        {formatDate(m.created_at)}
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        <span className="font-semibold text-slate-200 block truncate max-w-xs">
                                                            {m.item?.name}
                                                        </span>
                                                        <span className="text-[10px] text-slate-500 font-mono">
                                                            {m.item?.code}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-4 text-slate-400">
                                                        {m.warehouse?.name}
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold ${
                                                            m.direction === 'in'
                                                                ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                                                                : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'
                                                        }`}>
                                                            {m.movement_type === 'purchase_in' ? 'Entrada Compra' :
                                                             m.movement_type === 'production_out' ? 'Consumo OP' :
                                                             m.movement_type === 'adjustment_in' ? 'Ajuste (+)' :
                                                             m.movement_type === 'adjustment_out' ? 'Ajuste (-)' : m.movement_type}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-4 font-mono font-bold text-white">
                                                        {m.direction === 'in' ? '+' : '-'}{m.quantity} {m.item?.unit_of_measure}
                                                    </td>
                                                    <td className="py-3 px-4 font-mono">
                                                        {formatCurrency(m.unit_cost)}
                                                    </td>
                                                    <td className="py-3 px-4 font-mono">
                                                        {formatCurrency(m.total_cost)}
                                                    </td>
                                                    <td className="py-3 px-4 font-mono font-medium text-indigo-300">
                                                        {m.balance_quantity_after} {m.item?.unit_of_measure}
                                                    </td>
                                                    <td className="py-3 px-4 font-mono font-bold text-emerald-400">
                                                        {formatCurrency(m.balance_total_value_after)}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Paginação Kardex */}
                            {movements.links && movements.links.length > 3 && (
                                <div className="p-4 border-t border-slate-800 flex items-center justify-between">
                                    <span className="text-xs text-slate-400">
                                        Mostrando {movements.data.length} de {movements.total} movimentações
                                    </span>
                                    <div className="flex items-center gap-1">
                                        {movements.links.map((link, idx) => (
                                            <button
                                                key={idx}
                                                disabled={!link.url}
                                                onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className={`px-3 py-1 rounded-lg text-xs font-medium transition-colors ${
                                                    link.active
                                                        ? 'bg-indigo-600 text-white'
                                                        : link.url
                                                        ? 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                                                        : 'bg-transparent text-slate-600 cursor-not-allowed'
                                                }`}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>

            {/* ========================================================================= */}
            {/* MODAL: BAIXA DE TÍTULO FINANCEIRO                                         */}
            {/* ========================================================================= */}
            {selectedEntry && (
                <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl">
                        <div className="flex items-center justify-between pb-4 border-b border-slate-800">
                            <div>
                                <h3 className="text-base font-bold text-white tracking-tight">
                                    Registrar Baixa Financeira
                                </h3>
                                <p className="text-xs text-slate-400 mt-0.5">
                                    Título: <span className="font-mono text-indigo-400 font-semibold">{selectedEntry.entry_number}</span> - {selectedEntry.description}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setSelectedEntry(null)}
                                className="text-slate-400 hover:text-slate-200"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handlePaymentSubmit} className="mt-4 space-y-4 text-xs">
                            <div className="p-3 bg-slate-950 rounded-xl border border-slate-800 flex items-center justify-between">
                                <span className="text-slate-400">Saldo Devedor Atual:</span>
                                <span className="text-base font-bold text-white font-mono">
                                    {formatCurrency(selectedEntry.balance_due)}
                                </span>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-slate-400 mb-1 font-medium">Conta Bancária / Caixa *</label>
                                    <select
                                        value={paymentForm.data.bank_account_id}
                                        onChange={(e) => paymentForm.setData('bank_account_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500"
                                    >
                                        {bankAccounts.map((b) => (
                                            <option key={b.id} value={b.id}>
                                                {b.name} ({formatCurrency(b.current_balance)})
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-slate-400 mb-1 font-medium">Forma de Pagamento *</label>
                                    <select
                                        value={paymentForm.data.payment_method}
                                        onChange={(e) => paymentForm.setData('payment_method', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="pix">PIX Instantâneo</option>
                                        <option value="boleto">Boleto Bancário</option>
                                        <option value="credit_card">Cartão de Crédito</option>
                                        <option value="bank_transfer">Transferência / TED</option>
                                        <option value="cash">Dinheiro em Espécie</option>
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-slate-400 mb-1 font-medium">Valor Efetivo da Baixa (R$) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={paymentForm.data.amount}
                                        onChange={(e) => paymentForm.setData('amount', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 font-mono focus:outline-none focus:border-indigo-500"
                                    />
                                    {paymentForm.errors.amount && (
                                        <span className="text-rose-400 text-[10px] mt-1 block">{paymentForm.errors.amount}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-slate-400 mb-1 font-medium">Data do Pagamento *</label>
                                    <input
                                        type="date"
                                        value={paymentForm.data.payment_date}
                                        onChange={(e) => paymentForm.setData('payment_date', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-slate-400 mb-1 font-medium">Desconto Concedido (R$)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={paymentForm.data.discount_amount}
                                        onChange={(e) => paymentForm.setData('discount_amount', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 font-mono focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-slate-400 mb-1 font-medium">Juros / Multa (R$)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={paymentForm.data.interest_fine_amount}
                                        onChange={(e) => paymentForm.setData('interest_fine_amount', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 font-mono focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-slate-400 mb-1 font-medium">Observações / Autenticação Bancária</label>
                                <textarea
                                    value={paymentForm.data.notes}
                                    onChange={(e) => paymentForm.setData('notes', e.target.value)}
                                    rows={2}
                                    placeholder="Ex: Comprovante Pix banco Inter..."
                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div className="flex justify-end gap-3 pt-3 border-t border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setSelectedEntry(null)}
                                    className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-semibold"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={paymentForm.processing}
                                    className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-semibold shadow-md shadow-indigo-600/20 disabled:opacity-50"
                                >
                                    {paymentForm.processing ? 'Processando...' : 'Confirmar Baixa'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
