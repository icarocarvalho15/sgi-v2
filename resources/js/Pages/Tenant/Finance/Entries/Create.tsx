import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '../../../../Layouts/AppLayout';
import { 
    DollarSign, 
    ArrowLeft, 
    ArrowDownLeft, 
    ArrowUpRight, 
    Calendar, 
    Info 
} from 'lucide-react';

interface Partner {
    id: string;
    name: string;
    type: string;
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

interface BankAccount {
    id: string;
    name: string;
}

interface Props {
    partners: Partner[];
    chartOfAccounts: ChartOfAccount[];
    costCenters: CostCenter[];
    bankAccounts: BankAccount[];
}

export default function EntriesCreate({ partners, chartOfAccounts, costCenters, bankAccounts }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        type: 'receivable',
        partner_id: partners[0]?.id || '',
        chart_of_account_id: chartOfAccounts[0]?.id || '',
        cost_center_id: costCenters[0]?.id || '',
        bank_account_id: bankAccounts[0]?.id || '',
        description: '',
        original_amount: '',
        first_due_date: new Date(Date.now() + 30 * 86400000).toISOString().split('T')[0],
        competence_date: new Date().toISOString().split('T')[0],
        total_installments: 1,
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/finance/entries');
    };

    return (
        <AppLayout title="Novo Lançamento Financeiro">
            <Head title="Novo Título - SGI v2.0" />

            <div className="max-w-3xl mx-auto space-y-6">
                {/* Header */}
                <div>
                    <Link
                        href="/finance/entries"
                        className="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-200 transition-colors mb-2"
                    >
                        <ArrowLeft className="w-3.5 h-3.5" />
                        Voltar para Títulos Financeiros
                    </Link>
                    <h1 className="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                        <DollarSign className="w-7 h-7 text-emerald-400" />
                        Lançar Título / Contas a Pagar e Receber
                    </h1>
                    <p className="text-sm text-slate-400 mt-1">
                        Cadastre despesas operacionais ou receitas com suporte a divisão automática em parcelas.
                    </p>
                </div>

                {/* Form Card */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm">
                    <form onSubmit={handleSubmit} className="space-y-5">
                        {/* Type Switch */}
                        <div>
                            <label className="block text-sm font-medium text-slate-300 mb-2">
                                Tipo de Operação Financeira *
                            </label>
                            <div className="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    onClick={() => setData('type', 'receivable')}
                                    className={`py-3 px-4 rounded-xl border text-sm font-medium flex items-center justify-center gap-2 transition-all ${
                                        data.type === 'receivable'
                                            ? 'bg-emerald-600/15 border-emerald-500 text-emerald-400 ring-1 ring-emerald-500'
                                            : 'bg-slate-950 border-slate-800 text-slate-400 hover:bg-slate-800/50'
                                    }`}
                                >
                                    <ArrowDownLeft className="w-4 h-4 text-emerald-400" />
                                    Conta a Receber (Receita de Venda)
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setData('type', 'payable')}
                                    className={`py-3 px-4 rounded-xl border text-sm font-medium flex items-center justify-center gap-2 transition-all ${
                                        data.type === 'payable'
                                            ? 'bg-rose-600/15 border-rose-500 text-rose-400 ring-1 ring-rose-500'
                                            : 'bg-slate-950 border-slate-800 text-slate-400 hover:bg-slate-800/50'
                                    }`}
                                >
                                    <ArrowUpRight className="w-4 h-4 text-rose-400" />
                                    Conta a Pagar (Insumo / Despesa)
                                </button>
                            </div>
                        </div>

                        {/* Description */}
                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">
                                Descrição do Título *
                            </label>
                            <input
                                type="text"
                                placeholder="Ex: Fornecimento de 1.000 Catálogos Couché; ou Compra de Chapas CTP"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500"
                                required
                            />
                            {errors.description && <p className="text-xs text-rose-400 mt-1">{errors.description}</p>}
                        </div>

                        {/* Partner and Bank Account */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Cliente / Fornecedor
                                </label>
                                <select
                                    value={data.partner_id}
                                    onChange={(e) => setData('partner_id', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                                >
                                    <option value="">Nenhum (Lançamento Avulso)</option>
                                    {partners.map((p) => (
                                        <option key={p.id} value={p.id}>{p.name}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Conta Bancária Prevista
                                </label>
                                <select
                                    value={data.bank_account_id}
                                    onChange={(e) => setData('bank_account_id', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                                >
                                    <option value="">Padrão da Empresa</option>
                                    {bankAccounts.map((b) => (
                                        <option key={b.id} value={b.id}>{b.name}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {/* Amount and Installments */}
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Valor Total (R$) *
                                </label>
                                <input
                                    type="number"
                                    step="0.0001"
                                    min="0.0001"
                                    placeholder="Ex: 1500.00"
                                    value={data.original_amount}
                                    onChange={(e) => setData('original_amount', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 font-mono focus:outline-none focus:border-emerald-500"
                                    required
                                />
                                {errors.original_amount && <p className="text-xs text-rose-400 mt-1">{errors.original_amount}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Nº de Parcelas *
                                </label>
                                <select
                                    value={data.total_installments}
                                    onChange={(e) => setData('total_installments', parseInt(e.target.value, 10))}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                                >
                                    {[1, 2, 3, 4, 5, 6, 10, 12, 24].map((n) => (
                                        <option key={n} value={n}>{n}x {n > 1 ? 'Mensais' : 'À Vista'}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    1º Vencimento *
                                </label>
                                <input
                                    type="date"
                                    value={data.first_due_date}
                                    onChange={(e) => setData('first_due_date', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
                                    required
                                />
                            </div>
                        </div>

                        {/* Chart of accounts and Cost center */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Plano de Contas
                                </label>
                                <select
                                    value={data.chart_of_account_id}
                                    onChange={(e) => setData('chart_of_account_id', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                                >
                                    <option value="">Selecione...</option>
                                    {chartOfAccounts.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.code} - {c.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Centro de Custo
                                </label>
                                <select
                                    value={data.cost_center_id}
                                    onChange={(e) => setData('cost_center_id', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                                >
                                    <option value="">Selecione...</option>
                                    {costCenters.map((cc) => (
                                        <option key={cc.id} value={cc.id}>
                                            {cc.code} - {cc.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {/* Notes */}
                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">
                                Observações
                            </label>
                            <textarea
                                rows={2}
                                placeholder="Condições negociadas, número da nota fiscal ou chave Pix..."
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500"
                            />
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                            <Link
                                href="/finance/entries"
                                className="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium transition-colors"
                            >
                                Cancelar
                            </Link>

                            <button
                                type="submit"
                                disabled={processing}
                                className="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-sm font-medium transition-colors shadow-lg shadow-emerald-600/20"
                            >
                                {processing ? 'Gerando Parcelas...' : 'Salvar Lançamento'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
