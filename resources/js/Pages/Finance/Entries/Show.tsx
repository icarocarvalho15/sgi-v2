import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import FinancialSubNav from '@/Pages/Finance/Components/FinancialSubNav';
import { 
    ArrowLeft, 
    Calendar, 
    Clock, 
    DollarSign, 
    CheckCircle2, 
    AlertCircle, 
    RotateCcw, 
    X,
    Building2,
    Layers,
    FileText,
    TrendingUp,
    TrendingDown,
    ShieldAlert
} from 'lucide-react';

interface Partner {
    id: string;
    name: string;
    trade_name?: string;
    document_number?: string;
    email?: string;
    phone?: string;
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

interface FinancialPayment {
    id: string;
    financial_account_id: string;
    payment_method: string;
    payment_date: string;
    amount: string;
    interest_paid: string;
    fine_paid: string;
    discount_applied: string;
    net_amount: string;
    is_reversed: boolean;
    reversal_reason?: string;
    reversed_at?: string;
    account?: FinancialAccount;
}

interface FinancialEntry {
    id: string;
    entry_type: 'RECEIVABLE' | 'PAYABLE';
    document_number: string;
    title: string;
    original_amount: string;
    balance_amount: string;
    paid_amount: string;
    interest_amount: string;
    fine_amount: string;
    discount_amount: string;
    installment_number: number;
    total_installments: number;
    issue_date: string;
    due_date: string;
    competence_date: string;
    status: 'PENDING' | 'PARTIALLY_PAID' | 'PAID' | 'CANCELLED';
    notes?: string;
    partner?: Partner;
    chart_of_account?: ChartOfAccount;
    cost_center?: CostCenter;
    payments: FinancialPayment[];
}

interface Props {
    entry: FinancialEntry;
    accounts: FinancialAccount[];
}

export default function EntryShow({ entry, accounts }: Props) {
    const isReceivable = entry.entry_type === 'RECEIVABLE';

    // Reverse Modal
    const [reversingPayment, setReversingPayment] = useState<FinancialPayment | null>(null);
    const reverseForm = useForm({
        reason: '',
    });

    // Settle Modal
    const [isSettleOpen, setIsSettleOpen] = useState(false);
    const settleForm = useForm({
        financial_account_id: accounts?.[0]?.id || '',
        payment_method: 'PIX',
        payment_date: new Date().toISOString().split('T')[0],
        amount: entry.balance_amount,
        interest_paid: '0.00',
        fine_paid: '0.00',
        discount_applied: '0.00',
        notes: '',
    });

    const formatBRL = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num || 0);
    };

    const handleReverseSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!reversingPayment) return;

        reverseForm.post(`/app/finance/payments/${reversingPayment.id}/reverse`, {
            onSuccess: () => {
                setReversingPayment(null);
                reverseForm.reset();
            },
        });
    };

    const handleSettleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        settleForm.post(`/app/finance/entries/${entry.id}/settle`, {
            onSuccess: () => {
                setIsSettleOpen(false);
                settleForm.reset();
            },
        });
    };

    return (
        <TenantDesktopLayout title={`Título ${entry.document_number}`}>
            <Head title={`Título ${entry.document_number} - SGI`} />

            <div className="p-6 max-w-7xl mx-auto space-y-6">
                <FinancialSubNav />

                {/* Topbar com navegação */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                        <Link
                            href={`/app/finance/entries?entry_type=${entry.entry_type}`}
                            className="p-2 text-slate-400 hover:text-white bg-slate-900 border border-slate-800 rounded-lg transition"
                        >
                            <ArrowLeft className="w-4 h-4" />
                        </Link>
                        <div>
                            <div className="flex items-center space-x-2">
                                <h1 className="text-xl font-bold text-white font-mono">{entry.document_number}</h1>
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
                                        Pendente
                                    </span>
                                )}
                            </div>
                            <p className="text-xs text-slate-400">
                                Parcela {entry.installment_number} de {entry.total_installments} • {entry.title}
                            </p>
                        </div>
                    </div>

                    {entry.status !== 'PAID' && entry.status !== 'CANCELLED' && (
                        <button
                            onClick={() => setIsSettleOpen(true)}
                            className="flex items-center space-x-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-lg shadow-sm transition"
                        >
                            <DollarSign className="w-4 h-4" />
                            <span>Registrar Baixa</span>
                        </button>
                    )}
                </div>

                {/* Cards de Resumo Financeiro */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                        <span className="text-xs text-slate-400">Valor Original</span>
                        <p className="text-xl font-bold text-white mt-1">{formatBRL(entry.original_amount)}</p>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                        <span className="text-xs text-slate-400">Total Pago (Amortizado)</span>
                        <p className="text-xl font-bold text-emerald-400 mt-1">{formatBRL(entry.paid_amount)}</p>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                        <span className="text-xs text-slate-400">Saldo Pendente</span>
                        <p className="text-xl font-bold text-rose-400 mt-1">{formatBRL(entry.balance_amount)}</p>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                        <span className="text-xs text-slate-400">Data de Vencimento</span>
                        <p className="text-xl font-bold text-slate-200 mt-1">
                            {new Date(entry.due_date).toLocaleDateString('pt-BR')}
                        </p>
                    </div>
                </div>

                {/* Detalhes do Título */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-6">
                    <h2 className="text-sm font-bold text-white mb-4">Informações Complementares</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 text-xs">
                        <div>
                            <span className="text-slate-400 block mb-1">Parceiro Comercial:</span>
                            <span className="text-slate-200 font-semibold">{entry.partner?.name || 'Não informado'}</span>
                            {entry.partner?.document_number && (
                                <span className="block text-slate-400 text-[11px] font-mono">{entry.partner.document_number}</span>
                            )}
                        </div>

                        <div>
                            <span className="text-slate-400 block mb-1">Plano de Contas:</span>
                            <span className="text-indigo-400 font-semibold">
                                {entry.chart_of_account?.code} - {entry.chart_of_account?.name}
                            </span>
                        </div>

                        <div>
                            <span className="text-slate-400 block mb-1">Centro de Custo:</span>
                            <span className="text-slate-200 font-semibold">
                                {entry.cost_center ? `${entry.cost_center.code} - ${entry.cost_center.name}` : 'Não vinculado'}
                            </span>
                        </div>

                        <div>
                            <span className="text-slate-400 block mb-1">Data de Emissão:</span>
                            <span className="text-slate-200">
                                {entry.issue_date ? new Date(entry.issue_date).toLocaleDateString('pt-BR') : '-'}
                            </span>
                        </div>

                        <div>
                            <span className="text-slate-400 block mb-1">Data de Competência:</span>
                            <span className="text-slate-200">
                                {entry.competence_date ? new Date(entry.competence_date).toLocaleDateString('pt-BR') : '-'}
                            </span>
                        </div>

                        <div>
                            <span className="text-slate-400 block mb-1">Juros / Multa / Desconto Acumulados:</span>
                            <span className="text-slate-200 font-mono">
                                +{formatBRL(entry.interest_amount)} Juros | +{formatBRL(entry.fine_amount)} Multa | -{formatBRL(entry.discount_amount)} Desc.
                            </span>
                        </div>
                    </div>

                    {entry.notes && (
                        <div className="mt-4 pt-4 border-t border-slate-800 text-xs">
                            <span className="text-slate-400 block mb-1">Observações:</span>
                            <p className="text-slate-300 italic">{entry.notes}</p>
                        </div>
                    )}
                </div>

                {/* Histórico Imutável de Baixas / Pagamentos */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div className="p-4 border-b border-slate-800 flex items-center justify-between">
                        <div className="flex items-center space-x-2">
                            <Clock className="w-4 h-4 text-indigo-400" />
                            <h2 className="text-sm font-bold text-white">Histórico de Liquidações & Baixas</h2>
                        </div>
                        <span className="text-xs text-slate-400">
                            {entry.payments?.length || 0} registro(s)
                        </span>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-950/60 text-slate-400 font-semibold border-b border-slate-800 uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3 px-4">Data da Baixa</th>
                                    <th className="py-3 px-4">Conta Bancária</th>
                                    <th className="py-3 px-4">Forma</th>
                                    <th className="py-3 px-4 text-right">Amortização</th>
                                    <th className="py-3 px-4 text-right">Acréscimos/Descontos</th>
                                    <th className="py-3 px-4 text-right">Valor Líquido</th>
                                    <th className="py-3 px-4 text-center">Status</th>
                                    <th className="py-3 px-4 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {entry.payments.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="py-8 text-center text-slate-400">
                                            Nenhuma baixa registrada até o momento.
                                        </td>
                                    </tr>
                                ) : (
                                    entry.payments.map((payment) => (
                                        <tr key={payment.id} className={payment.is_reversed ? 'opacity-50 bg-rose-950/10' : 'hover:bg-slate-800/40 transition'}>
                                            <td className="py-3 px-4 font-medium text-slate-200">
                                                {new Date(payment.payment_date).toLocaleDateString('pt-BR')}
                                            </td>

                                            <td className="py-3 px-4 text-slate-300">
                                                {payment.account?.name || 'Conta Financeira'}
                                            </td>

                                            <td className="py-3 px-4 text-slate-400">
                                                {payment.payment_method}
                                            </td>

                                            <td className="py-3 px-4 text-right font-semibold text-white">
                                                {formatBRL(payment.amount)}
                                            </td>

                                            <td className="py-3 px-4 text-right text-slate-400 font-mono text-[11px]">
                                                +{formatBRL(payment.interest_paid)} / -{formatBRL(payment.discount_applied)}
                                            </td>

                                            <td className="py-3 px-4 text-right font-bold text-emerald-400">
                                                {formatBRL(payment.net_amount)}
                                            </td>

                                            <td className="py-3 px-4 text-center">
                                                {payment.is_reversed ? (
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-500/15 text-rose-400 border border-rose-500/20" title={payment.reversal_reason}>
                                                        Estornado
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">
                                                        Compensado
                                                    </span>
                                                )}
                                            </td>

                                            <td className="py-3 px-4 text-right">
                                                {!payment.is_reversed && (
                                                    <button
                                                        onClick={() => setReversingPayment(payment)}
                                                        className="px-2 py-1 bg-rose-600/15 hover:bg-rose-600/25 text-rose-400 border border-rose-500/30 rounded text-[10px] font-medium transition inline-flex items-center space-x-1"
                                                    >
                                                        <RotateCcw className="w-3 h-3" />
                                                        <span>Estornar</span>
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Modal de Confirmação de Estorno */}
                {reversingPayment && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                        <div className="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-md overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                            <div className="p-5 border-b border-slate-800 flex items-center justify-between">
                                <div className="flex items-center space-x-2.5">
                                    <div className="p-2 bg-rose-500/10 text-rose-400 rounded-lg">
                                        <ShieldAlert className="w-5 h-5" />
                                    </div>
                                    <h2 className="text-base font-bold text-white">Estornar Liquidação</h2>
                                </div>
                                <button
                                    onClick={() => setReversingPayment(null)}
                                    className="text-slate-400 hover:text-white transition"
                                >
                                    <X className="w-5 h-5" />
                                </button>
                            </div>

                            <form onSubmit={handleReverseSubmit} className="p-6 space-y-4">
                                <p className="text-xs text-slate-300">
                                    O estorno irá reverter a amortização de <strong>{formatBRL(reversingPayment.amount)}</strong> e atualizar o saldo da conta bancária de forma atômica.
                                </p>

                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">
                                        Justificativa Obrigatória do Estorno <span className="text-rose-400">*</span>
                                    </label>
                                    <textarea
                                        required
                                        rows={3}
                                        value={reverseForm.data.reason}
                                        onChange={(e) => reverseForm.setData('reason', e.target.value)}
                                        placeholder="Informe o motivo para fins de conciliação e auditoria..."
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg p-3 text-xs text-white focus:outline-none focus:border-rose-500"
                                    />
                                    {reverseForm.errors.reason && (
                                        <p className="text-[11px] text-rose-400 mt-1">{reverseForm.errors.reason}</p>
                                    )}
                                </div>

                                <div className="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                                    <button
                                        type="button"
                                        onClick={() => setReversingPayment(null)}
                                        className="px-4 py-2 text-xs font-medium text-slate-400 hover:text-white transition"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={reverseForm.processing}
                                        className="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold rounded-lg shadow-sm transition disabled:opacity-50"
                                    >
                                        {reverseForm.processing ? 'Estornando...' : 'Confirmar Estorno'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Modal de Baixa Rápida */}
                {isSettleOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                        <div className="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-lg overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                            <div className="p-5 border-b border-slate-800 flex items-center justify-between">
                                <div className="flex items-center space-x-2.5">
                                    <div className="p-2 bg-emerald-500/10 text-emerald-400 rounded-lg">
                                        <DollarSign className="w-5 h-5" />
                                    </div>
                                    <h2 className="text-base font-bold text-white">Baixa de Título</h2>
                                </div>
                                <button
                                    onClick={() => setIsSettleOpen(false)}
                                    className="text-slate-400 hover:text-white transition"
                                >
                                    <X className="w-5 h-5" />
                                </button>
                            </div>

                            <form onSubmit={handleSettleSubmit} className="p-6 space-y-4">
                                <div className="p-3 bg-slate-950 border border-slate-800 rounded-lg flex items-center justify-between text-xs">
                                    <span className="text-slate-400">Saldo Pendente Atual:</span>
                                    <span className="font-bold text-white text-sm">
                                        {formatBRL(entry.balance_amount)}
                                    </span>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="col-span-2">
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Conta Financeira <span className="text-rose-400">*</span>
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
                                            Forma de Pagamento
                                        </label>
                                        <select
                                            value={settleForm.data.payment_method}
                                            onChange={(e) => settleForm.setData('payment_method', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        >
                                            <option value="PIX">PIX</option>
                                            <option value="BOLETO">Boleto Bancário</option>
                                            <option value="BANK_TRANSFER">Transferência</option>
                                            <option value="CREDIT_CARD">Cartão de Crédito</option>
                                            <option value="DEBIT_CARD">Cartão de Débito</option>
                                            <option value="CASH">Espécie</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Data da Baixa
                                        </label>
                                        <input
                                            type="date"
                                            value={settleForm.data.payment_date}
                                            onChange={(e) => settleForm.setData('payment_date', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>

                                    <div className="col-span-2">
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Valor da Baixa (R$)
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            max={entry.balance_amount}
                                            required
                                            value={settleForm.data.amount}
                                            onChange={(e) => settleForm.setData('amount', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-bold"
                                        />
                                    </div>
                                </div>

                                <div className="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                                    <button
                                        type="button"
                                        onClick={() => setIsSettleOpen(false)}
                                        className="px-4 py-2 text-xs font-medium text-slate-400 hover:text-white transition"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={settleForm.processing}
                                        className="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-lg shadow-sm transition disabled:opacity-50"
                                    >
                                        {settleForm.processing ? 'Registrando...' : 'Confirmar Baixa'}
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
