import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../../../Layouts/AppLayout';
import { 
    DollarSign, 
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
    Download
} from 'lucide-react';
import { exportFinancialEntriesToCsv } from '../../../../Utils/exportToCsv';

interface Partner {
    id: string;
    name: string;
    document?: string;
}

interface BankAccount {
    id: string;
    name: string;
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

interface Props {
    entries: {
        data: FinancialEntry[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    bankAccounts: BankAccount[];
    filters: {
        type?: string;
        status?: string;
        search?: string;
    };
    metrics: {
        total_receivable: string;
        total_payable: string;
    };
}

export default function EntriesIndex({ entries, bankAccounts, filters, metrics }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [type, setType] = useState(filters.type || '');
    const [status, setStatus] = useState(filters.status || '');

    // Modal de baixa
    const [selectedEntry, setSelectedEntry] = useState<FinancialEntry | null>(null);
    const { data, setData, post, processing, errors, reset } = useForm({
        financial_entry_id: '',
        bank_account_id: bankAccounts[0]?.id || '',
        payment_method: 'pix',
        amount: '',
        discount_amount: '0.00',
        interest_fine_amount: '0.00',
        payment_date: new Date().toISOString().split('T')[0],
        notes: '',
    });

    const openPaymentModal = (entry: FinancialEntry) => {
        setSelectedEntry(entry);
        setData({
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
        post('/finance/payments', {
            onSuccess: () => {
                setSelectedEntry(null);
                reset();
            },
        });
    };

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/finance/entries', {
            type: type || undefined,
            status: status || undefined,
            search: search || undefined,
        }, { preserveState: true });
    };

    const formatCurrency = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num || 0);
    };

    const formatDate = (dateStr: string) => {
        if (!dateStr) return '-';
        const [year, month, day] = dateStr.split('T')[0].split('-');
        return `${day}/${month}/${year}`;
    };

    const statusBadge = (s: string) => {
        switch (s) {
            case 'paid':
                return <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"><CheckCircle2 className="w-3 h-3" /> Liquidado</span>;
            case 'partial':
                return <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-amber-500/10 text-amber-400 border border-amber-500/20"><Clock className="w-3 h-3" /> Parcial</span>;
            case 'cancelled':
                return <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-slate-800 text-slate-400 border border-slate-700">Cancelado</span>;
            default:
                return <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-sky-500/10 text-sky-400 border border-sky-500/20"><Clock className="w-3 h-3" /> Em Aberto</span>;
        }
    };

    return (
        <AppLayout title="Contas a Pagar e Receber">
            <Head title="Financeiro - SGI v2.0" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                            <DollarSign className="w-7 h-7 text-emerald-400" />
                            Gestão Financeira & Títulos
                        </h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Controle unificado de Contas a Pagar e Receber com parcelamento, juros e conciliação bancária.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => exportFinancialEntriesToCsv(entries.data)}
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-medium border border-slate-700 transition-colors shadow-sm"
                            title="Exportar títulos em formato compatível com Excel e Google Sheets"
                        >
                            <Download className="w-4 h-4 text-emerald-400" />
                            Exportar Planilha (CSV)
                        </button>
                        <Link
                            href="/finance?tab=reports"
                            className="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-medium border border-slate-700 transition-colors shadow-sm"
                        >
                            <Wallet className="w-4 h-4 text-indigo-400" />
                            Fluxo & DRE
                        </Link>
                        <Link
                            href="/finance/entries/create"
                            className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium transition-colors shadow-lg shadow-emerald-600/20"
                        >
                            <PlusCircle className="w-4 h-4" />
                            Novo Lançamento
                        </Link>
                    </div>
                </div>

                {/* Metrics Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Total em Aberto a Receber
                            </span>
                            <div className="p-2 rounded-lg bg-emerald-500/10 text-emerald-400">
                                <ArrowDownLeft className="w-5 h-5" />
                            </div>
                        </div>
                        <div className="text-2xl font-bold text-emerald-400 mt-2 font-mono">
                            {formatCurrency(metrics.total_receivable)}
                        </div>
                        <span className="text-xs text-slate-400 font-medium mt-1 inline-block">
                            Receitas pendentes e parciais
                        </span>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Total em Aberto a Pagar
                            </span>
                            <div className="p-2 rounded-lg bg-rose-500/10 text-rose-400">
                                <ArrowUpRight className="w-5 h-5" />
                            </div>
                        </div>
                        <div className="text-2xl font-bold text-rose-400 mt-2 font-mono">
                            {formatCurrency(metrics.total_payable)}
                        </div>
                        <span className="text-xs text-slate-400 font-medium mt-1 inline-block">
                            Insumos, fornecedores e despesas operacionais
                        </span>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-sm">
                    <form onSubmit={handleFilter} className="flex flex-col md:flex-row gap-3">
                        <div className="relative flex-1">
                            <Search className="w-4 h-4 absolute left-3 top-3 text-slate-500" />
                            <input
                                type="text"
                                placeholder="Buscar por descrição ou número do título..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-9 pr-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <select
                            value={type}
                            onChange={(e) => setType(e.target.value)}
                            className="bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">Todos os Tipos</option>
                            <option value="receivable">A Receber</option>
                            <option value="payable">A Pagar</option>
                        </select>

                        <select
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                            className="bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">Todos os Status</option>
                            <option value="pending">Em Aberto</option>
                            <option value="partial">Parcial</option>
                            <option value="paid">Liquidado</option>
                        </select>

                        <button
                            type="submit"
                            className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-medium rounded-lg border border-slate-700 transition-colors"
                        >
                            Filtrar
                        </button>
                    </form>
                </div>

                {/* Table */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr className="border-b border-slate-800 bg-slate-950/50 text-slate-400 text-xs uppercase font-semibold">
                                    <th className="py-3 px-4">Tipo / Título</th>
                                    <th className="py-3 px-4">Cliente / Fornecedor</th>
                                    <th className="py-3 px-4">Vencimento</th>
                                    <th className="py-3 px-4 text-right">Valor Líquido</th>
                                    <th className="py-3 px-4 text-right">Valor Pago</th>
                                    <th className="py-3 px-4 text-right">Saldo Devedor</th>
                                    <th className="py-3 px-4 text-center">Status</th>
                                    <th className="py-3 px-4 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {entries.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="py-12 text-center text-slate-500">
                                            Nenhum título financeiro encontrado para os filtros selecionados.
                                        </td>
                                    </tr>
                                ) : (
                                    entries.data.map((entry) => {
                                        const isRec = entry.type === 'receivable';
                                        return (
                                            <tr key={entry.id} className="hover:bg-slate-800/30 transition-colors">
                                                <td className="py-3.5 px-4">
                                                    <div className="flex items-center gap-1.5 font-medium text-slate-200">
                                                        {isRec ? (
                                                            <ArrowDownLeft className="w-4 h-4 text-emerald-400 shrink-0" />
                                                        ) : (
                                                            <ArrowUpRight className="w-4 h-4 text-rose-400 shrink-0" />
                                                        )}
                                                        <span>{entry.description}</span>
                                                    </div>
                                                    <div className="text-xs text-slate-400 font-mono mt-0.5 ml-5">
                                                        {entry.entry_number}
                                                    </div>
                                                </td>
                                                <td className="py-3.5 px-4 text-slate-300">
                                                    {entry.partner?.name || '-'}
                                                </td>
                                                <td className="py-3.5 px-4 text-slate-300 font-mono">
                                                    {formatDate(entry.due_date)}
                                                </td>
                                                <td className="py-3.5 px-4 text-right font-mono font-medium text-slate-200">
                                                    {formatCurrency(entry.net_amount)}
                                                </td>
                                                <td className="py-3.5 px-4 text-right font-mono text-emerald-400">
                                                    {formatCurrency(entry.paid_amount)}
                                                </td>
                                                <td className="py-3.5 px-4 text-right font-mono font-bold text-white">
                                                    {formatCurrency(entry.balance_due)}
                                                </td>
                                                <td className="py-3.5 px-4 text-center">
                                                    {statusBadge(entry.status)}
                                                </td>
                                                <td className="py-3.5 px-4 text-center">
                                                    {entry.status !== 'paid' && entry.status !== 'cancelled' ? (
                                                        <button
                                                            onClick={() => openPaymentModal(entry)}
                                                            className="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-500 text-white font-medium transition-colors"
                                                        >
                                                            Baixar
                                                        </button>
                                                    ) : (
                                                        <span className="text-xs text-slate-500">-</span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {entries.last_page > 1 && (
                        <div className="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                            <div>
                                Mostrando página {entries.current_page} de {entries.last_page} ({entries.total} títulos)
                            </div>
                            <div className="flex gap-1">
                                {entries.links.map((link, idx) => (
                                    <Link
                                        key={idx}
                                        href={link.url || '#'}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-2.5 py-1 rounded border text-xs ${
                                            link.active
                                                ? 'bg-emerald-600 text-white border-emerald-600'
                                                : 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700'
                                        } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal de Baixa Financeira */}
            {selectedEntry && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h2 className="text-lg font-bold text-white flex items-center gap-2">
                                <DollarSign className="w-5 h-5 text-emerald-400" />
                                Baixa de Título Financeiro
                            </h2>
                            <button
                                onClick={() => setSelectedEntry(null)}
                                className="text-slate-400 hover:text-white"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <div className="bg-slate-950 p-3 rounded-xl border border-slate-800 text-xs space-y-1">
                            <div className="flex justify-between text-slate-400">
                                <span>Título:</span>
                                <span className="font-mono text-slate-200">{selectedEntry.entry_number}</span>
                            </div>
                            <div className="flex justify-between text-slate-400">
                                <span>Descrição:</span>
                                <span className="text-slate-200">{selectedEntry.description}</span>
                            </div>
                            <div className="flex justify-between text-slate-400">
                                <span>Saldo Devedor:</span>
                                <span className="font-mono font-bold text-emerald-400">{formatCurrency(selectedEntry.balance_due)}</span>
                            </div>
                        </div>

                        <form onSubmit={handlePaymentSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    Valor a Baixar (R$) *
                                </label>
                                <input
                                    type="number"
                                    step="0.0001"
                                    min="0.0001"
                                    max={selectedEntry.balance_due}
                                    value={data.amount}
                                    onChange={(e) => setData('amount', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 font-mono focus:outline-none focus:border-emerald-500"
                                    required
                                />
                                {errors.amount && <p className="text-xs text-rose-400 mt-1">{errors.amount}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">
                                        Conta Bancária *
                                    </label>
                                    <select
                                        value={data.bank_account_id}
                                        onChange={(e) => setData('bank_account_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                                        required
                                    >
                                        {bankAccounts.map((b) => (
                                            <option key={b.id} value={b.id}>{b.name}</option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">
                                        Forma de Pagto *
                                    </label>
                                    <select
                                        value={data.payment_method}
                                        onChange={(e) => setData('payment_method', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                                        required
                                    >
                                        <option value="pix">PIX</option>
                                        <option value="boleto">Boleto</option>
                                        <option value="credit_card">Cartão de Crédito</option>
                                        <option value="bank_transfer">Transferência</option>
                                        <option value="cash">Dinheiro</option>
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">
                                        Desconto (R$)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.0001"
                                        value={data.discount_amount}
                                        onChange={(e) => setData('discount_amount', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 font-mono focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">
                                        Juros / Multa (R$)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.0001"
                                        value={data.interest_fine_amount}
                                        onChange={(e) => setData('interest_fine_amount', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 font-mono focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    Observações da Baixa
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ex: Baixa via comprovante PIX recebido no WhatsApp"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
                                />
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setSelectedEntry(null)}
                                    className="px-4 py-2 rounded-lg bg-slate-800 text-slate-300 text-sm font-medium hover:bg-slate-700 transition-colors"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium transition-colors shadow-lg shadow-emerald-600/20"
                                >
                                    {processing ? 'Processando...' : 'Confirmar Baixa'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
