import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import CommercialSubNav from '@/Pages/CRM/Components/CommercialSubNav';
import { Item, UnitOfMeasure } from '@/types';
import { 
    FileText, 
    ArrowLeft, 
    Building2, 
    Calendar, 
    Clock, 
    DollarSign, 
    Percent, 
    Cpu, 
    CheckCircle2, 
    AlertTriangle, 
    ShoppingCart,
    Layers,
    UserCheck,
    Mail,
    Phone,
    MapPin
} from 'lucide-react';

interface PartnerAddress {
    id: string;
    street: string;
    number: string;
    complement?: string;
    district: string;
    city: string;
    state: string;
}

interface Partner {
    id: string;
    name: string;
    trade_name?: string;
    document_number: string;
    email?: string;
    phone?: string;
    addresses?: PartnerAddress[];
}

interface Deal {
    id: string;
    title: string;
}

interface Bom {
    id: string;
    code: string;
    version: number;
    total_estimated_cost: string;
}

interface QuoteItem {
    id: string;
    item: Item & { unit_of_measure?: UnitOfMeasure };
    bom?: Bom;
    quantity: string;
    unit_cost: string;
    markup_percentage: string;
    unit_price: string;
    total_price: string;
    lead_time_days: number;
    notes?: string;
}

interface SalesOrder {
    id: string;
    order_number: string;
    status: string;
}

interface Quote {
    id: string;
    quote_number: string;
    status: 'DRAFT' | 'SENT' | 'APPROVED' | 'REJECTED' | 'CONVERTED';
    total_materials_cost: string;
    total_operations_cost: string;
    markup_percentage: string;
    margin_percentage: string;
    final_total: string;
    lead_time_days: number;
    valid_until?: string;
    approved_at?: string;
    converted_at?: string;
    notes?: string;
    created_at: string;
    partner?: Partner;
    deal?: Deal;
    items: QuoteItem[];
    sales_order?: SalesOrder;
}

interface Props {
    quote: Quote;
}

export default function QuoteShow({ quote }: Props) {
    const [converting, setConverting] = useState(false);

    const formatCurrency = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(isNaN(num) ? 0 : num);
    };

    const handleConvert = () => {
        if (!confirm('Deseja converter este Orçamento em Pedido de Venda e disparar as Ordens de Produção correspondentes?')) {
            return;
        }

        setConverting(true);
        router.post(`/app/sales/quotes/${quote.id}/convert`, {}, {
            onFinish: () => setConverting(false),
        });
    };

    const getStatusBadge = (st: string) => {
        switch (st) {
            case 'CONVERTED':
                return <span className="px-3 py-1 text-xs font-bold rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Convertido em Pedido</span>;
            case 'APPROVED':
                return <span className="px-3 py-1 text-xs font-semibold rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20">Aprovado</span>;
            case 'SENT':
                return <span className="px-3 py-1 text-xs font-semibold rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">Enviado</span>;
            case 'REJECTED':
                return <span className="px-3 py-1 text-xs font-semibold rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">Recusado</span>;
            default:
                return <span className="px-3 py-1 text-xs font-semibold rounded-lg bg-slate-500/10 text-slate-400 border border-slate-500/20">Rascunho</span>;
        }
    };

    const defaultAddr = quote.partner?.addresses?.[0];

    return (
        <TenantDesktopLayout title={`Orçamento ${quote.quote_number}`}>
            <Head title={`Orçamento ${quote.quote_number}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <CommercialSubNav />

                {/* Top Action Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/app/sales/quotes"
                            className="p-2 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-xl font-bold text-white font-mono">{quote.quote_number}</h1>
                                {getStatusBadge(quote.status)}
                            </div>
                            <p className="text-xs text-slate-400 mt-0.5">
                                Criado em {new Date(quote.created_at).toLocaleDateString('pt-BR')}
                                {quote.deal && ` • Oportunidade: ${quote.deal.title}`}
                            </p>
                        </div>
                    </div>

                    {/* Botão de Conversão 1-Clique */}
                    <div className="flex items-center gap-3">
                        {quote.status === 'CONVERTED' && quote.sales_order ? (
                            <Link
                                href={`/app/sales/orders/${quote.sales_order.id}`}
                                className="inline-flex items-center gap-2 bg-emerald-600/20 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-600/30 px-4 py-2.5 rounded-xl text-sm font-bold transition shadow-sm"
                            >
                                <ShoppingCart className="w-4 h-4" />
                                Visualizar Pedido {quote.sales_order.order_number}
                            </Link>
                        ) : (
                            <button
                                onClick={handleConvert}
                                disabled={converting}
                                className="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition shadow-lg shadow-emerald-950/50 disabled:opacity-50"
                            >
                                <CheckCircle2 className="w-5 h-5" />
                                {converting ? 'Convertendo...' : 'Converter em Pedido de Venda (1-Clique)'}
                            </button>
                        )}
                    </div>
                </div>

                {/* Grid de Métricas Comerciais & Engenharia */}
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                    <div className="bg-slate-900 border border-slate-800 p-4 rounded-xl">
                        <div className="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5 mb-1">
                            <DollarSign className="w-3.5 h-3.5 text-indigo-400" />
                            Total Final
                        </div>
                        <div className="text-lg font-bold text-white font-mono truncate">
                            {formatCurrency(quote.final_total)}
                        </div>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 p-4 rounded-xl">
                        <div className="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5 mb-1">
                            <Percent className="w-3.5 h-3.5 text-emerald-400" />
                            Margem Bruta
                        </div>
                        <div className="text-lg font-bold text-emerald-400 font-mono">
                            {parseFloat(quote.margin_percentage || '0').toFixed(1)}%
                        </div>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 p-4 rounded-xl">
                        <div className="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5 mb-1">
                            <Percent className="w-3.5 h-3.5 text-amber-400" />
                            Markup Aplicado
                        </div>
                        <div className="text-lg font-bold text-amber-400 font-mono">
                            {parseFloat(quote.markup_percentage || '0').toFixed(1)}%
                        </div>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 p-4 rounded-xl">
                        <div className="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5 mb-1">
                            <Clock className="w-3.5 h-3.5 text-blue-400" />
                            Prazo de Entrega
                        </div>
                        <div className="text-lg font-bold text-white font-mono">
                            {quote.lead_time_days || 15} dias
                        </div>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 p-4 rounded-xl">
                        <div className="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5 mb-1">
                            <Layers className="w-3.5 h-3.5 text-slate-400" />
                            Custo Materiais
                        </div>
                        <div className="text-base font-bold text-slate-300 font-mono truncate">
                            {formatCurrency(quote.total_materials_cost)}
                        </div>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 p-4 rounded-xl">
                        <div className="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5 mb-1">
                            <Cpu className="w-3.5 h-3.5 text-purple-400" />
                            Custo Processos
                        </div>
                        <div className="text-base font-bold text-slate-300 font-mono truncate">
                            {formatCurrency(quote.total_operations_cost)}
                        </div>
                    </div>
                </div>

                {/* Dados do Parceiro / Cliente */}
                {quote.partner && (
                    <div className="bg-slate-900 border border-slate-800 p-5 rounded-xl mb-6">
                        <h3 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                            Dados do Cliente
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                            <div className="space-y-1">
                                <div className="font-bold text-white text-sm">{quote.partner.name}</div>
                                {quote.partner.trade_name && (
                                    <div className="text-slate-400">{quote.partner.trade_name}</div>
                                )}
                                <div className="text-slate-400 font-mono">CNPJ/CPF: {quote.partner.document_number}</div>
                            </div>
                            <div className="space-y-1 text-slate-300">
                                {quote.partner.email && (
                                    <div className="flex items-center gap-1.5">
                                        <Mail className="w-3.5 h-3.5 text-slate-500" />
                                        <span>{quote.partner.email}</span>
                                    </div>
                                )}
                                {quote.partner.phone && (
                                    <div className="flex items-center gap-1.5">
                                        <Phone className="w-3.5 h-3.5 text-slate-500" />
                                        <span>{quote.partner.phone}</span>
                                    </div>
                                )}
                            </div>
                            <div>
                                {defaultAddr ? (
                                    <div className="flex items-start gap-1.5 text-slate-300">
                                        <MapPin className="w-3.5 h-3.5 text-slate-500 shrink-0 mt-0.5" />
                                        <span>
                                            {defaultAddr.street}, {defaultAddr.number} - {defaultAddr.district}<br />
                                            {defaultAddr.city}/{defaultAddr.state}
                                        </span>
                                    </div>
                                ) : (
                                    <span className="text-slate-500">Endereço não cadastrado</span>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Itens do Orçamento */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm mb-6">
                    <div className="p-4 border-b border-slate-800 flex items-center justify-between">
                        <h3 className="text-sm font-bold text-white">Composição de Itens do Orçamento</h3>
                        <span className="text-xs text-slate-400 font-mono">
                            {quote.items.length} {quote.items.length === 1 ? 'item' : 'itens'}
                        </span>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs text-slate-300">
                            <thead className="bg-slate-950/70 border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th className="py-3 px-4">Item / Produto</th>
                                    <th className="py-3 px-4">Ficha Técnica (BOM)</th>
                                    <th className="py-3 px-4 text-right">Qtd</th>
                                    <th className="py-3 px-4 text-right">Custo Unit.</th>
                                    <th className="py-3 px-4 text-right">Markup %</th>
                                    <th className="py-3 px-4 text-right">Preço Unit.</th>
                                    <th className="py-3 px-4 text-right">Total Item</th>
                                    <th className="py-3 px-4 text-center">Prazo</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {quote.items.map((it) => (
                                    <tr key={it.id} className="hover:bg-slate-800/40 transition">
                                        <td className="py-3 px-4">
                                            <div className="font-bold text-white">{it.item.name}</div>
                                            <div className="text-[10px] text-slate-500 font-mono">SKU: {it.item.sku}</div>
                                        </td>
                                        <td className="py-3 px-4">
                                            {it.bom ? (
                                                <div className="flex items-center gap-1.5 text-indigo-400 font-medium">
                                                    <Cpu className="w-3.5 h-3.5 shrink-0" />
                                                    <span>{it.bom.code} (v{it.bom.version})</span>
                                                </div>
                                            ) : (
                                                <span className="text-slate-500 text-[11px]">Item de Catálogo</span>
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-right font-mono text-white">
                                            {parseFloat(it.quantity).toFixed(2)} {it.item.unit_of_measure?.code || 'UN'}
                                        </td>
                                        <td className="py-3 px-4 text-right font-mono text-slate-400">
                                            {formatCurrency(it.unit_cost)}
                                        </td>
                                        <td className="py-3 px-4 text-right font-mono text-amber-400">
                                            {parseFloat(it.markup_percentage).toFixed(1)}%
                                        </td>
                                        <td className="py-3 px-4 text-right font-mono font-semibold text-slate-200">
                                            {formatCurrency(it.unit_price)}
                                        </td>
                                        <td className="py-3 px-4 text-right font-mono font-bold text-white text-sm">
                                            {formatCurrency(it.total_price)}
                                        </td>
                                        <td className="py-3 px-4 text-center text-slate-400">
                                            {it.lead_time_days || 0}d
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {quote.notes && (
                    <div className="bg-slate-900 border border-slate-800 p-4 rounded-xl text-xs text-slate-300">
                        <span className="font-semibold text-slate-400 block mb-1">Observações da Proposta:</span>
                        <p className="whitespace-pre-wrap">{quote.notes}</p>
                    </div>
                )}
            </div>
        </TenantDesktopLayout>
    );
}
