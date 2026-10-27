import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import FinancialSubNav from '@/Pages/Finance/Components/FinancialSubNav';
import { PaginatedResponse } from '@/types';
import { 
    Plus, 
    Search, 
    ArrowRight, 
    Calendar, 
    DollarSign, 
    CheckCircle2, 
    AlertCircle, 
    X,
    Filter,
    Clock,
    TrendingUp,
    TrendingDown,
    Building2,
    Layers
} from 'lucide-react';

interface Partner {
    id: string;
    name: string;
    partner_type: string;
}

interface FinancialAccount {
    id: string;
    name: string;
    bank_name?: string;
    current_balance: string;
}

interface ChartOfAccount {
    id: string;
    code: string;
    name: string;
    type: string;
}

interface CostCenter {
    id: string;
    code: string;
    name: string;
}

interface FinancialEntry {
    id: string;
    entry_type: 'RECEIVABLE' | 'PAYABLE';
    document_number: string;
    title: string;
    original_amount: string;
    balance_amount: string;
    paid_amount: string;
    installment_number: number;
    total_installments: number;
    issue_date: string;
    due_date: string;
    competence_date: string;
    status: 'PENDING' | 'PARTIALLY_PAID' | 'PAID' | 'CANCELLED';
    partner?: Partner;
    chart_of_account?: ChartOfAccount;
    cost_center?: CostCenter;
}

interface Props {
    entries: PaginatedResponse<FinancialEntry>;
    filters: {
        entry_type: string;
        status?: string;
        search?: string;
        start_date?: string;
        end_date?: string;
    };
    accounts: FinancialAccount[];
    chartOfAccounts: ChartOfAccount[];
    costCenters: CostCenter[];
    partners: Partner[];
}

export default function EntriesIndex({
    entries,
    filters,
    accounts,
    chartOfAccounts,
    costCenters,
    partners,
}: Props) {
    const isReceivable = filters.entry_type === 'RECEIVABLE';
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');

    // Modals
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [settleEntry, setSettleEntry] = useState<FinancialEntry | null>(null);

    // Filter submit
    const applyFilters = (newParams: Record<string, string>) => {
        router.get('/app/finance/entries', {
            entry_type: filters.entry_type,
            status,
            search,
            start_date: startDate,
            end_date: endDate,
            ...newParams,
        }, { preserveState: true, replace: true });
    };

    // Create Entry Form
    const createForm = useForm({
        entry_type: filters.entry_type,
        chart_of_account_id: chartOfAccounts?.[0]?.id || '',
        partner_id: partners?.[0]?.id || '',
        cost_center_id: costCenters?.[0]?.id || '',
        title: '',
        document_number: '',
        total_amount: '',
        installments_count: 1,
        first_due_date: new Date().toISOString().split('T')[0],
        competence_date: new Date().toISOString().split('T')[0],
        interval_days: 30,
        notes: '',
    });

    // Settle Payment Form
    const settleForm = useForm({
        financial_account_id: accounts?.[0]?.id || '',
        payment_method: 'PIX',
        payment_date: new Date().toISOString().split('T')[0],
        amount: '',
        interest_paid: '0.00',
        fine_paid: '0.00',
        discount_applied: '0.00',
        notes: '',
    });

    const openSettleModal = (entry: FinancialEntry) => {
        setSettleEntry(entry);
        settleForm.setData({
            financial_account_id: accounts?.[0]?.id || '',
            payment_method: 'PIX',
            payment_date: new Date().toISOString().split('T')[0],
            amount: entry.balance_amount,
            interest_paid: '0.00',
            fine_paid: '0.00',
            discount_applied: '0.00',
            notes: '',
        });
    };

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/app/finance/entries', {
            onSuccess: () => {
                setIsCreateOpen(false);
                createForm.reset();
            },
        });
    };

    const handleSettleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!settleEntry) return;

        settleForm.post(`/app/finance/entries/${settleEntry.id}/settle`, {
            onSuccess: () => {
                setSettleEntry(null);
                settleForm.reset();
            },
        });
    };

    // Net preview calculation for settlement
    const netAmountPreview = (() => {
        const amt = parseFloat(settleForm.data.amount) || 0;
        const interest = parseFloat(settleForm.data.interest_paid) || 0;
        const fine = parseFloat(settleForm.data.fine_paid) || 0;
        const disc = parseFloat(settleForm.data.discount_applied) || 0;
        return (amt + interest + fine - disc).toFixed(2);
    })();

    const formatBRL = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num || 0);
    };

    return (
        <TenantDesktopLayout title={isReceivable ? 'Contas a Receber' : 'Contas a Pagar'}>
            <Head title={isReceivable ? 'Contas a Receber - SGI' : 'Contas a Pagar - SGI'} />

            <div className="p-6 max-w-7xl mx-auto space-y-6">
                <FinancialSubNav />

                {/* Header do Módulo */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center space-x-2.5">
                            {isReceivable ? (
                                <div className="p-2 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-lg">
                                    <TrendingUp className="w-5 h-5" />
                                </div>
                            ) : (
                                <div className="p-2 bg-rose-500/10 text-rose-400 border border-rose-500/20 rounded-lg">
                                    <TrendingDown className="w-5 h-5" />
                                </div>
                            )}
                            <div>
                                <h1 className="text-xl font-bold text-white tracking-tight">
                                    {isReceivable ? 'Contas a Receber' : 'Contas a Pagar'}
                                </h1>
                                <p className="text-xs text-slate-400">
                                    {isReceivable 
                                        ? 'Gerencie recebimentos de clientes, parcelamentos e baixas bancárias.'
                                        : 'Controle duplicatas de fornecedores, impostos e despesas operacionais.'}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center space-x-3">
                        <button
                            onClick={() => setIsCreateOpen(true)}
                            className="flex items-center space-x-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm transition"
                        >
                            <Plus className="w-4 h-4" />
                            <span>{isReceivable ? 'Novo Título a Receber' : 'Novo Título a Pagar'}</span>
                        </button>
                    </div>
                </div>

                {/* Barra de Filtros */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col md:flex-row items-center justify-between gap-3 shadow-sm">
                    <div className="relative w-full md:w-80">
                        <Search className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                            type="text"
                            placeholder="Buscar título, documento ou parceiro..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && applyFilters({ search })}
                            className="w-full pl-9 pr-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-xs text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"
                        />
                    </div>

                    <div className="flex items-center gap-2 w-full md:w-auto overflow-x-auto">
                        <select
                            value={status}
                            onChange={(e) => {
                                setStatus(e.target.value);
                                applyFilters({ status: e.target.value });
                            }}
                            className="bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-300 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Todos os Status</option>
                            <option value="PENDING">Pendentes</option>
                            <option value="PARTIALLY_PAID">Parcialmente Baixados</option>
                            <option value="PAID">Liquidados</option>
                            <option value="CANCELLED">Cancelados</option>
                        </select>

                        <div className="flex items-center space-x-1.5 bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-400">
                            <Calendar className="w-3.5 h-3.5 text-slate-400" />
                            <input
                                type="date"
                                value={startDate}
                                onChange={(e) => {
                                    setStartDate(e.target.value);
                                    applyFilters({ start_date: e.target.value });
                                }}
                                className="bg-transparent text-xs text-slate-200 focus:outline-none"
                            />
                            <span className="text-slate-400">até</span>
                            <input
                                type="date"
                                value={endDate}
                                onChange={(e) => {
                                    setEndDate(e.target.value);
                                    applyFilters({ end_date: e.target.value });
                                }}
                                className="bg-transparent text-xs text-slate-200 focus:outline-none"
                            />
                        </div>
                    </div>
                </div>

                {/* Tabela de Títulos */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-950/60 text-slate-400 font-semibold border-b border-slate-800 uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3 px-4">Documento / Parcela</th>
                                    <th className="py-3 px-4">Título & Categoria</th>
                                    <th className="py-3 px-4">Parceiro Comercial</th>
                                    <th className="py-3 px-4">Vencimento</th>
                                    <th className="py-3 px-4 text-right">Valor Original</th>
                                    <th className="py-3 px-4 text-right">Saldo Pendente</th>
                                    <th className="py-3 px-4 text-center">Status</th>
                                    <th className="py-3 px-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {entries.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="py-12 text-center text-slate-400">
                                            Nenhum título financeiro encontrado para estes filtros.
                                        </td>
                                    </tr>
                                ) : (
                                    entries.data.map((entry) => {
                                        const isOverdue = entry.status === 'PENDING' && new Date(entry.due_date) < new Date();

                                        return (
                                            <tr key={entry.id} className="hover:bg-slate-800/40 transition">
                                                <td className="py-3.5 px-4">
                                                    <div className="font-mono font-medium text-slate-200">
                                                        {entry.document_number}
                                                    </div>
                                                    <div className="text-[10px] text-slate-400">
                                                        Parcela {entry.installment_number} de {entry.total_installments}
                                                    </div>
                                                </td>

                                                <td className="py-3.5 px-4">
                                                    <div className="font-semibold text-white">
                                                        {entry.title}
                                                    </div>
                                                    <div className="text-[10px] text-indigo-400 flex items-center space-x-1">
                                                        <span>{entry.chart_of_account?.code} - {entry.chart_of_account?.name}</span>
                                                    </div>
                                                </td>

                                                <td className="py-3.5 px-4">
                                                    <div className="text-slate-200 font-medium">
                                                        {entry.partner?.name || 'Não informado'}
                                                    </div>
                                                    {entry.cost_center && (
                                                        <div className="text-[10px] text-slate-400">
                                                            CC: {entry.cost_center.name}
                                                        </div>
                                                    )}
                                                </td>

                                                <td className="py-3.5 px-4">
                                                    <div className={`flex items-center space-x-1.5 font-medium ${isOverdue ? 'text-rose-400 font-bold' : 'text-slate-300'}`}>
                                                        <Clock className="w-3.5 h-3.5" />
                                                        <span>{new Date(entry.due_date).toLocaleDateString('pt-BR')}</span>
                                                    </div>
                                                    {isOverdue && (
                                                        <span className="text-[10px] text-rose-500 font-semibold">Vencido</span>
                                                    )}
                                                </td>

                                                <td className="py-3.5 px-4 text-right font-medium text-slate-300">
                                                    {formatBRL(entry.original_amount)}
                                                </td>

                                                <td className="py-3.5 px-4 text-right font-bold text-white">
                                                    {formatBRL(entry.balance_amount)}
                                                </td>

                                                <td className="py-3.5 px-4 text-center">
                                                    {entry.status === 'PAID' && (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">
                                                            Liquidado
                                                        </span>
                                                    )}
                                                    {entry.status === 'PARTIALLY_PAID' && (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/15 text-amber-400 border border-amber-500/20">
                                                            Baixa Parcial
                                                        </span>
                                                    )}
                                                    {entry.status === 'PENDING' && (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-500/15 text-blue-400 border border-blue-500/20">
                                                            Aberto
                                                        </span>
                                                    )}
                                                    {entry.status === 'CANCELLED' && (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-400">
                                                            Cancelado
                                                        </span>
                                                    )}
                                                </td>

                                                <td className="py-3.5 px-4 text-right">
                                                    <div className="flex items-center justify-end space-x-2">
                                                        {entry.status !== 'PAID' && entry.status !== 'CANCELLED' && (
                                                            <button
                                                                onClick={() => openSettleModal(entry)}
                                                                className="px-2.5 py-1 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 rounded text-[11px] font-medium transition"
                                                            >
                                                                Baixar
                                                            </button>
                                                        )}
                                                        <Link
                                                            href={`/app/finance/entries/${entry.id}`}
                                                            className="p-1 text-slate-400 hover:text-white hover:bg-slate-800 rounded transition"
                                                            title="Ver Detalhes"
                                                        >
                                                            <ArrowRight className="w-4 h-4" />
                                                        </Link>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Modal de Criação de Título */}
                {isCreateOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                        <div className="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-2xl overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                            <div className="p-5 border-b border-slate-800 flex items-center justify-between">
                                <div className="flex items-center space-x-2.5">
                                    <div className="p-2 bg-indigo-500/10 text-indigo-400 rounded-lg">
                                        <Plus className="w-5 h-5" />
                                    </div>
                                    <h2 className="text-base font-bold text-white">
                                        {isReceivable ? 'Cadastrar Título a Receber' : 'Cadastrar Título a Pagar'}
                                    </h2>
                                </div>
                                <button
                                    onClick={() => setIsCreateOpen(false)}
                                    className="text-slate-400 hover:text-white transition"
                                >
                                    <X className="w-5 h-5" />
                                </button>
                            </div>

                            <form onSubmit={handleCreateSubmit} className="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="md:col-span-2">
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Descrição / Título <span className="text-rose-400">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            required
                                            value={createForm.data.title}
                                            onChange={(e) => createForm.setData('title', e.target.value)}
                                            placeholder="Ex: Fatura Fornecedor de Matéria-Prima Lote 40"
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        />
                                        {createForm.errors.title && (
                                            <p className="text-[11px] text-rose-400 mt-1">{createForm.errors.title}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Número do Documento / NF
                                        </label>
                                        <input
                                            type="text"
                                            value={createForm.data.document_number}
                                            onChange={(e) => createForm.setData('document_number', e.target.value)}
                                            placeholder="Ex: NF-10492"
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Valor Total (R$) <span className="text-rose-400">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            required
                                            value={createForm.data.total_amount}
                                            onChange={(e) => createForm.setData('total_amount', e.target.value)}
                                            placeholder="0.00"
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-bold"
                                        />
                                        {createForm.errors.total_amount && (
                                            <p className="text-[11px] text-rose-400 mt-1">{createForm.errors.total_amount}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Parceiro Comercial
                                        </label>
                                        <select
                                            value={createForm.data.partner_id}
                                            onChange={(e) => createForm.setData('partner_id', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        >
                                            <option value="">Selecione um parceiro (opcional)</option>
                                            {partners.map((p) => (
                                                <option key={p.id} value={p.id}>{p.name}</option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Plano de Contas (Categoria) <span className="text-rose-400">*</span>
                                        </label>
                                        <select
                                            required
                                            value={createForm.data.chart_of_account_id}
                                            onChange={(e) => createForm.setData('chart_of_account_id', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        >
                                            {chartOfAccounts.map((c) => (
                                                <option key={c.id} value={c.id}>{c.code} - {c.name}</option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Centro de Custo
                                        </label>
                                        <select
                                            value={createForm.data.cost_center_id}
                                            onChange={(e) => createForm.setData('cost_center_id', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        >
                                            <option value="">Selecione um centro de custo</option>
                                            {costCenters.map((cc) => (
                                                <option key={cc.id} value={cc.id}>{cc.code} - {cc.name}</option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Data de Competência
                                        </label>
                                        <input
                                            type="date"
                                            value={createForm.data.competence_date}
                                            onChange={(e) => createForm.setData('competence_date', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Qtd. de Parcelas
                                        </label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="72"
                                            value={createForm.data.installments_count}
                                            onChange={(e) => createForm.setData('installments_count', parseInt(e.target.value) || 1)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            1º Vencimento <span className="text-rose-400">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            required
                                            value={createForm.data.first_due_date}
                                            onChange={(e) => createForm.setData('first_due_date', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                </div>

                                <div className="p-3 bg-slate-950/80 border border-slate-800 rounded-lg text-xs text-slate-400 flex items-center justify-between">
                                    <span>Divisão em {createForm.data.installments_count} parcela(s)</span>
                                    <span className="font-semibold text-slate-200">
                                        {createForm.data.total_amount && createForm.data.installments_count > 1 
                                            ? `Aprox. ${formatBRL(parseFloat(createForm.data.total_amount) / createForm.data.installments_count)} por parcela`
                                            : formatBRL(createForm.data.total_amount || 0)}
                                    </span>
                                </div>

                                <div className="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                                    <button
                                        type="button"
                                        onClick={() => setIsCreateOpen(false)}
                                        className="px-4 py-2 text-xs font-medium text-slate-400 hover:text-white transition"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={createForm.processing}
                                        className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm transition disabled:opacity-50"
                                    >
                                        {createForm.processing ? 'Gerando...' : 'Confirmar e Salvar'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Modal de Baixa / Liquidação */}
                {settleEntry && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                        <div className="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-lg overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                            <div className="p-5 border-b border-slate-800 flex items-center justify-between">
                                <div className="flex items-center space-x-2.5">
                                    <div className="p-2 bg-emerald-500/10 text-emerald-400 rounded-lg">
                                        <DollarSign className="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h2 className="text-base font-bold text-white">Baixa de Título</h2>
                                        <p className="text-[11px] text-slate-400 font-mono">
                                            {settleEntry.document_number} - {settleEntry.title}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    onClick={() => setSettleEntry(null)}
                                    className="text-slate-400 hover:text-white transition"
                                >
                                    <X className="w-5 h-5" />
                                </button>
                            </div>

                            <form onSubmit={handleSettleSubmit} className="p-6 space-y-4">
                                <div className="p-3 bg-slate-950 border border-slate-800 rounded-lg flex items-center justify-between text-xs">
                                    <span className="text-slate-400">Saldo Pendente Atual:</span>
                                    <span className="font-bold text-white text-sm">
                                        {formatBRL(settleEntry.balance_amount)}
                                    </span>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="col-span-2">
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Conta Financeira / Caixa Destino <span className="text-rose-400">*</span>
                                        </label>
                                        <select
                                            required
                                            value={settleForm.data.financial_account_id}
                                            onChange={(e) => settleForm.setData('financial_account_id', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        >
                                            {accounts.map((acc) => (
                                                <option key={acc.id} value={acc.id}>
                                                    {acc.name} ({formatBRL(acc.current_balance)})
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Forma de Pagamento <span className="text-rose-400">*</span>
                                        </label>
                                        <select
                                            required
                                            value={settleForm.data.payment_method}
                                            onChange={(e) => settleForm.setData('payment_method', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        >
                                            <option value="PIX">PIX</option>
                                            <option value="BOLETO">Boleto Bancário</option>
                                            <option value="BANK_TRANSFER">Transferência (TED/DOC)</option>
                                            <option value="CREDIT_CARD">Cartão de Crédito</option>
                                            <option value="DEBIT_CARD">Cartão de Débito</option>
                                            <option value="CASH">Dinheiro em Espécie</option>
                                            <option value="OTHER">Outros</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Data da Baixa <span className="text-rose-400">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            required
                                            value={settleForm.data.payment_date}
                                            onChange={(e) => settleForm.setData('payment_date', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Valor Principal da Baixa (R$) <span className="text-rose-400">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            max={settleEntry.balance_amount}
                                            required
                                            value={settleForm.data.amount}
                                            onChange={(e) => settleForm.setData('amount', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-bold"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Juros Pagos (R$)
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={settleForm.data.interest_paid}
                                            onChange={(e) => settleForm.setData('interest_paid', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Multa Paga (R$)
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={settleForm.data.fine_paid}
                                            onChange={(e) => settleForm.setData('fine_paid', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Desconto Concedido (R$)
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={settleForm.data.discount_applied}
                                            onChange={(e) => settleForm.setData('discount_applied', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                </div>

                                <div className="p-3 bg-emerald-950/30 border border-emerald-800/40 rounded-lg flex items-center justify-between text-xs">
                                    <span className="text-emerald-300 font-medium">Impacto Líquido na Conta:</span>
                                    <span className="font-extrabold text-emerald-400 text-sm">
                                        {formatBRL(netAmountPreview)}
                                    </span>
                                </div>

                                <div className="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                                    <button
                                        type="button"
                                        onClick={() => setSettleEntry(null)}
                                        className="px-4 py-2 text-xs font-medium text-slate-400 hover:text-white transition"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={settleForm.processing}
                                        className="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-lg shadow-sm transition disabled:opacity-50"
                                    >
                                        {settleForm.processing ? 'Registrando Baixa...' : 'Confirmar Liquidação'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </TenantDesktopLayout>
    );
}
