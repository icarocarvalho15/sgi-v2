import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import CommercialSubNav from '@/Pages/CRM/Components/CommercialSubNav';
import { PaginatedResponse, Item } from '@/types';
import { 
    FileText, 
    Plus, 
    Search, 
    Building2, 
    ArrowRight, 
    Calendar, 
    Clock, 
    DollarSign, 
    Percent, 
    Trash2, 
    X,
    Calculator,
    CheckCircle2
} from 'lucide-react';

interface Partner {
    id: string;
    name: string;
    trade_name?: string;
    document_number: string;
}

interface Deal {
    id: string;
    title: string;
    partner_id: string;
}

interface Bom {
    id: string;
    code: string;
    version: number;
    total_estimated_cost: string;
}

interface ExtendedItem extends Item {
    boms?: Bom[];
}

interface Quote {
    id: string;
    quote_number: string;
    partner_id: string;
    deal_id?: string;
    status: 'DRAFT' | 'SENT' | 'APPROVED' | 'REJECTED' | 'CONVERTED';
    total_materials_cost: string;
    total_operations_cost: string;
    markup_percentage: string;
    margin_percentage: string;
    final_total: string;
    lead_time_days: number;
    valid_until?: string;
    partner?: Partner;
    deal?: Deal;
    items_count?: number;
    created_at: string;
}

interface Props {
    quotes: PaginatedResponse<Quote>;
    partners: Partner[];
    deals: Deal[];
    items: ExtendedItem[];
    filters: {
        search?: string;
        status?: string;
    };
}

interface QuoteFormItem {
    item_id: string;
    bom_id: string;
    quantity: string;
    markup_percentage: string;
    notes: string;
}

export default function QuotesIndex({ quotes, partners, deals, items, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [isCreateOpen, setIsCreateOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        partner_id: partners?.[0]?.id || '',
        deal_id: '',
        markup_percentage: '30.00',
        lead_time_days: 15,
        valid_until: '',
        notes: '',
        items: [
            {
                item_id: items?.[0]?.id || '',
                bom_id: items?.[0]?.boms?.[0]?.id || '',
                quantity: '1.0000',
                markup_percentage: '30.00',
                notes: '',
            },
        ] as QuoteFormItem[],
    });

    const formatCurrency = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(isNaN(num) ? 0 : num);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/app/sales/quotes', { search, status }, { preserveState: true });
    };

    const handleStatusFilter = (newStatus: string) => {
        setStatus(newStatus);
        router.get('/app/sales/quotes', { search, status: newStatus }, { preserveState: true });
    };

    const addItemRow = () => {
        const firstItem = items[0];
        setData('items', [
            ...data.items,
            {
                item_id: firstItem?.id || '',
                bom_id: firstItem?.boms?.[0]?.id || '',
                quantity: '1.0000',
                markup_percentage: data.markup_percentage,
                notes: '',
            },
        ]);
    };

    const removeItemRow = (index: number) => {
        if (data.items.length <= 1) return;
        setData('items', data.items.filter((_, i) => i !== index));
    };

    const updateItemRow = (index: number, field: keyof QuoteFormItem, value: string) => {
        const newItems = [...data.items];
        newItems[index] = { ...newItems[index], [field]: value };

        // Se trocou de item, atualiza a lista de BOMs correspondente
        if (field === 'item_id') {
            const selectedItem = items.find((it) => it.id === value);
            newItems[index].bom_id = selectedItem?.boms?.[0]?.id || '';
        }

        setData('items', newItems);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/app/sales/quotes', {
            onSuccess: () => {
                setIsCreateOpen(false);
                reset();
            },
        });
    };

    const getStatusBadge = (st: string) => {
        switch (st) {
            case 'CONVERTED':
                return <span className="px-2 py-0.5 text-xs font-bold rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Convertido em Pedido</span>;
            case 'APPROVED':
                return <span className="px-2 py-0.5 text-xs font-semibold rounded bg-blue-500/10 text-blue-400 border border-blue-500/20">Aprovado</span>;
            case 'SENT':
                return <span className="px-2 py-0.5 text-xs font-semibold rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">Enviado</span>;
            case 'REJECTED':
                return <span className="px-2 py-0.5 text-xs font-semibold rounded bg-rose-500/10 text-rose-400 border border-rose-500/20">Recusado</span>;
            default:
                return <span className="px-2 py-0.5 text-xs font-semibold rounded bg-slate-500/10 text-slate-400 border border-slate-500/20">Rascunho</span>;
        }
    };

    return (
        <TenantDesktopLayout title="Orçamentos Técnicos - Vendas">
            <Head title="Orçamentos Sob Encomenda - Vendas" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <CommercialSubNav />

                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-xl font-bold text-white flex items-center gap-2">
                            <FileText className="w-6 h-6 text-indigo-400" />
                            Orçamentos Técnicos Sob Encomenda
                        </h1>
                        <p className="text-xs text-slate-400 mt-1">
                            Simulação de custos de engenharia (BOM), rollup de materiais e operações com markup, margem e conversão 1-clique.
                        </p>
                    </div>

                    <button
                        onClick={() => setIsCreateOpen(true)}
                        className="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm"
                    >
                        <Plus className="w-4 h-4" />
                        Novo Orçamento Técnico
                    </button>
                </div>

                {/* Filtros */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
                    <form onSubmit={handleSearch} className="flex-1 w-full flex items-center gap-2">
                        <div className="relative flex-1">
                            <Search className="w-4 h-4 text-slate-500 absolute left-3 top-3" />
                            <input
                                type="text"
                                placeholder="Buscar por número do orçamento ou cliente..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-9 pr-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition"
                            />
                        </div>
                        <button
                            type="submit"
                            className="bg-slate-800 hover:bg-slate-700 text-slate-200 px-4 py-2 rounded-lg text-sm font-medium transition"
                        >
                            Filtrar
                        </button>
                    </form>

                    <div className="flex items-center gap-1.5 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
                        {[
                            { key: '', label: 'Todos' },
                            { key: 'DRAFT', label: 'Rascunho' },
                            { key: 'SENT', label: 'Enviado' },
                            { key: 'APPROVED', label: 'Aprovado' },
                            { key: 'CONVERTED', label: 'Convertido' },
                        ].map((btn) => (
                            <button
                                key={btn.key}
                                onClick={() => handleStatusFilter(btn.key)}
                                className={`px-3 py-1.5 rounded-lg text-xs font-medium transition whitespace-nowrap ${
                                    status === btn.key
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-800'
                                }`}
                            >
                                {btn.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Tabela de Orçamentos */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs text-slate-300">
                            <thead className="bg-slate-950/70 border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th className="py-3 px-4">Orçamento</th>
                                    <th className="py-3 px-4">Cliente / Parceiro</th>
                                    <th className="py-3 px-4">Oportunidade (CRM)</th>
                                    <th className="py-3 px-4 text-right">Margem %</th>
                                    <th className="py-3 px-4 text-right">Total Final</th>
                                    <th className="py-3 px-4 text-center">Prazo</th>
                                    <th className="py-3 px-4 text-center">Status</th>
                                    <th className="py-3 px-4 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {quotes.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="py-12 text-center text-slate-500">
                                            Nenhum orçamento encontrado com os filtros aplicados.
                                        </td>
                                    </tr>
                                ) : (
                                    quotes.data.map((quote) => (
                                        <tr key={quote.id} className="hover:bg-slate-800/40 transition">
                                            <td className="py-3 px-4">
                                                <div className="font-mono font-bold text-indigo-400">{quote.quote_number}</div>
                                                <div className="text-[10px] text-slate-500">
                                                    {quote.items_count || 1} {quote.items_count === 1 ? 'item' : 'itens'}
                                                </div>
                                            </td>
                                            <td className="py-3 px-4">
                                                <div className="font-bold text-white">{quote.partner?.name || 'Cliente'}</div>
                                                <div className="text-[11px] text-slate-400 font-mono">{quote.partner?.document_number}</div>
                                            </td>
                                            <td className="py-3 px-4">
                                                {quote.deal ? (
                                                    <span className="text-slate-300 font-medium truncate block max-w-[180px]">
                                                        {quote.deal.title}
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-500">—</span>
                                                )}
                                            </td>
                                            <td className="py-3 px-4 text-right">
                                                <span className="font-bold text-emerald-400 font-mono">
                                                    {parseFloat(quote.margin_percentage || '0').toFixed(1)}%
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-right">
                                                <span className="font-bold text-white font-mono text-sm">
                                                    {formatCurrency(quote.final_total)}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-center">
                                                <span className="inline-flex items-center gap-1 text-slate-300">
                                                    <Clock className="w-3 h-3 text-slate-500" />
                                                    {quote.lead_time_days || 15}d
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-center">
                                                {getStatusBadge(quote.status)}
                                            </td>
                                            <td className="py-3 px-4 text-right">
                                                <Link
                                                    href={`/app/sales/quotes/${quote.id}`}
                                                    className="inline-flex items-center gap-1 bg-slate-800 hover:bg-slate-700 text-indigo-400 hover:text-indigo-300 px-3 py-1.5 rounded-lg font-semibold transition"
                                                >
                                                    <span>Detalhes</span>
                                                    <ArrowRight className="w-3 h-3" />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Modal Novo Orçamento Técnico */}
            {isCreateOpen && (
                <div className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-4xl max-h-[92vh] overflow-y-auto shadow-2xl">
                        <div className="p-6 border-b border-slate-800 flex items-center justify-between sticky top-0 bg-slate-900 z-10">
                            <div>
                                <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                    <Calculator className="w-5 h-5 text-indigo-400" />
                                    Novo Orçamento Técnico Sob Encomenda
                                </h3>
                                <p className="text-xs text-slate-400 mt-0.5">
                                    Conecte insumos da Ficha Técnica (BOM) para cálculo automático de markup, margem e prazo.
                                </p>
                            </div>
                            <button
                                onClick={() => setIsCreateOpen(false)}
                                className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Cliente e Oportunidade */}
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div className="sm:col-span-2">
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Cliente *</label>
                                    <select
                                        value={data.partner_id}
                                        onChange={(e) => setData('partner_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                        required
                                    >
                                        <option value="">Selecione o cliente...</option>
                                        {partners.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                {p.name} ({p.document_number})
                                            </option>
                                        ))}
                                    </select>
                                    {errors.partner_id && <p className="text-xs text-rose-400 mt-1">{errors.partner_id}</p>}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Oportunidade (CRM)</label>
                                    <select
                                        value={data.deal_id}
                                        onChange={(e) => setData('deal_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="">Sem vínculo com oportunidade</option>
                                        {deals.map((d) => (
                                            <option key={d.id} value={d.id}>
                                                {d.title}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Markup Base Padrão (%)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.markup_percentage}
                                        onChange={(e) => setData('markup_percentage', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 font-mono"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Validade da Proposta</label>
                                    <input
                                        type="date"
                                        value={data.valid_until}
                                        onChange={(e) => setData('valid_until', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Prazo Estimado (Dias)</label>
                                    <input
                                        type="number"
                                        value={data.lead_time_days}
                                        onChange={(e) => setData('lead_time_days', parseInt(e.target.value) || 15)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            {/* Itens do Orçamento */}
                            <div className="pt-3 border-t border-slate-800">
                                <div className="flex items-center justify-between mb-3">
                                    <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                        Itens Técnicos & Fichas de Composição
                                    </h4>
                                    <button
                                        type="button"
                                        onClick={addItemRow}
                                        className="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-700 text-indigo-400 px-3 py-1.5 rounded text-xs font-semibold transition"
                                    >
                                        <Plus className="w-3.5 h-3.5" />
                                        Adicionar Item
                                    </button>
                                </div>

                                <div className="space-y-3">
                                    {data.items.map((it, idx) => {
                                        const selectedItem = items.find((i) => i.id === it.item_id);
                                        const availableBoms = selectedItem?.boms || [];

                                        return (
                                            <div key={idx} className="bg-slate-950 border border-slate-800/80 p-3.5 rounded-xl space-y-3">
                                                <div className="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                                    <div className="sm:col-span-5">
                                                        <label className="block text-[11px] text-slate-400 mb-1">Produto / Item *</label>
                                                        <select
                                                            value={it.item_id}
                                                            onChange={(e) => updateItemRow(idx, 'item_id', e.target.value)}
                                                            className="w-full bg-slate-900 border border-slate-800 rounded px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                                            required
                                                        >
                                                            {items.map((item) => (
                                                                <option key={item.id} value={item.id}>
                                                                    [{item.sku}] {item.name}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </div>

                                                    <div className="sm:col-span-4">
                                                        <label className="block text-[11px] text-slate-400 mb-1">Ficha Técnica (BOM)</label>
                                                        <select
                                                            value={it.bom_id}
                                                            onChange={(e) => updateItemRow(idx, 'bom_id', e.target.value)}
                                                            className="w-full bg-slate-900 border border-slate-800 rounded px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                                        >
                                                            <option value="">Sem Ficha (Custo Padrão)</option>
                                                            {availableBoms.map((b) => (
                                                                <option key={b.id} value={b.id}>
                                                                    {b.code} (v{b.version}) - Custo: {formatCurrency(b.total_estimated_cost)}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </div>

                                                    <div className="sm:col-span-2">
                                                        <label className="block text-[11px] text-slate-400 mb-1">Quantidade *</label>
                                                        <input
                                                            type="number"
                                                            step="0.0001"
                                                            value={it.quantity}
                                                            onChange={(e) => updateItemRow(idx, 'quantity', e.target.value)}
                                                            className="w-full bg-slate-900 border border-slate-800 rounded px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 font-mono"
                                                            required
                                                        />
                                                    </div>

                                                    <div className="sm:col-span-1 flex justify-end">
                                                        <button
                                                            type="button"
                                                            disabled={data.items.length <= 1}
                                                            onClick={() => removeItemRow(idx)}
                                                            className="text-slate-500 hover:text-rose-400 p-1.5 rounded hover:bg-slate-900 transition disabled:opacity-20"
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Observações Comerciais</label>
                                <textarea
                                    rows={2}
                                    placeholder="Condições de pagamento, frete (FOB/CIF) ou observações técnicas..."
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setIsCreateOpen(false)}
                                    className="px-4 py-2 rounded-lg text-sm font-medium text-slate-400 hover:text-white transition"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2 rounded-lg text-sm font-semibold transition disabled:opacity-50"
                                >
                                    {processing ? 'Processando...' : 'Gerar Orçamento Técnico'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </TenantDesktopLayout>
    );
}
