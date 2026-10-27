import React from 'react';
import { Head, Link } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import CommercialSubNav from '@/Pages/CRM/Components/CommercialSubNav';
import { Item, UnitOfMeasure } from '@/types';
import { 
    ShoppingCart, 
    ArrowLeft, 
    Building2, 
    Calendar, 
    DollarSign, 
    Factory, 
    Cpu, 
    CheckCircle2, 
    Clock, 
    FileText,
    ArrowUpRight
} from 'lucide-react';

interface Partner {
    id: string;
    name: string;
    trade_name?: string;
    document_number: string;
    email?: string;
    phone?: string;
}

interface Quote {
    id: string;
    quote_number: string;
}

interface Bom {
    id: string;
    code: string;
    version: number;
}

interface ProductionOrder {
    id: string;
    order_number: string;
    status: string;
    planned_quantity: string;
    produced_quantity: string;
}

interface SalesOrderItem {
    id: string;
    item: Item & { unit_of_measure?: UnitOfMeasure };
    bom?: Bom;
    production_order?: ProductionOrder;
    quantity: string;
    unit_price: string;
    total_price: string;
}

interface SalesOrder {
    id: string;
    order_number: string;
    status: 'CONFIRMED' | 'IN_PRODUCTION' | 'SHIPPED' | 'COMPLETED' | 'CANCELLED';
    total_amount: string;
    delivery_date?: string;
    payment_terms?: string;
    notes?: string;
    created_at: string;
    partner?: Partner;
    quote?: Quote;
    items: SalesOrderItem[];
}

interface Props {
    order: SalesOrder;
}

export default function SalesOrderShow({ order }: Props) {
    const formatCurrency = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(isNaN(num) ? 0 : num);
    };

    const getStatusBadge = (st: string) => {
        switch (st) {
            case 'CONFIRMED':
                return <span className="px-3 py-1 text-xs font-semibold rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20">Confirmado</span>;
            case 'IN_PRODUCTION':
                return <span className="px-3 py-1 text-xs font-semibold rounded-lg bg-purple-500/10 text-purple-400 border border-purple-500/20">Em Produção</span>;
            case 'SHIPPED':
                return <span className="px-3 py-1 text-xs font-semibold rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">Expedido</span>;
            case 'COMPLETED':
                return <span className="px-3 py-1 text-xs font-bold rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Concluído</span>;
            default:
                return <span className="px-3 py-1 text-xs font-semibold rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">Cancelado</span>;
        }
    };

    return (
        <TenantDesktopLayout title={`Pedido de Venda ${order.order_number}`}>
            <Head title={`Pedido de Venda ${order.order_number}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <CommercialSubNav />

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/app/sales/orders"
                            className="p-2 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-xl font-bold text-white font-mono">{order.order_number}</h1>
                                {getStatusBadge(order.status)}
                            </div>
                            <p className="text-xs text-slate-400 mt-0.5">
                                Confirmado em {new Date(order.created_at).toLocaleDateString('pt-BR')}
                                {order.quote && (
                                    <span> • Gerado a partir do Orçamento: <strong className="text-indigo-400">{order.quote.quote_number}</strong></span>
                                )}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        {order.quote && (
                            <Link
                                href={`/app/sales/quotes/${order.quote.id}`}
                                className="inline-flex items-center gap-2 bg-slate-900 border border-slate-800 text-slate-300 hover:text-white px-4 py-2 rounded-lg text-xs font-semibold transition"
                            >
                                <FileText className="w-4 h-4 text-indigo-400" />
                                Ver Orçamento Original
                            </Link>
                        )}
                    </div>
                </div>

                {/* Cards Topo */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div className="bg-slate-900 border border-slate-800 p-4 rounded-xl">
                        <div className="text-xs text-slate-400 mb-1">Valor Total do Pedido</div>
                        <div className="text-xl font-bold text-white font-mono">
                            {formatCurrency(order.total_amount)}
                        </div>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 p-4 rounded-xl">
                        <div className="text-xs text-slate-400 mb-1">Data Prometida de Entrega</div>
                        <div className="text-base font-bold text-slate-200 flex items-center gap-2">
                            <Calendar className="w-4 h-4 text-indigo-400" />
                            {order.delivery_date ? new Date(order.delivery_date).toLocaleDateString('pt-BR') : 'A Combinar'}
                        </div>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 p-4 rounded-xl">
                        <div className="text-xs text-slate-400 mb-1">Condição de Pagamento</div>
                        <div className="text-base font-bold text-slate-200">
                            {order.payment_terms || 'Padrão (30 Dias)'}
                        </div>
                    </div>
                </div>

                {/* Cliente */}
                {order.partner && (
                    <div className="bg-slate-900 border border-slate-800 p-4 rounded-xl mb-6">
                        <h3 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Cliente</h3>
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between text-xs gap-2">
                            <div>
                                <span className="font-bold text-white text-sm">{order.partner.name}</span>
                                {order.partner.trade_name && <span className="text-slate-400 ml-2">({order.partner.trade_name})</span>}
                            </div>
                            <div className="font-mono text-slate-400">
                                CNPJ/CPF: {order.partner.document_number}
                            </div>
                        </div>
                    </div>
                )}

                {/* Itens do Pedido & Ordens de Produção Vinculadas */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div className="p-4 border-b border-slate-800 flex items-center justify-between">
                        <h3 className="text-sm font-bold text-white">Itens do Pedido & Execução Industrial</h3>
                        <span className="text-xs text-slate-400 font-mono">
                            {order.items.length} {order.items.length === 1 ? 'item' : 'itens'}
                        </span>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs text-slate-300">
                            <thead className="bg-slate-950/70 border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th className="py-3 px-4">Item / Produto</th>
                                    <th className="py-3 px-4">Ficha Técnica</th>
                                    <th className="py-3 px-4 text-center">Ordem de Produção (Chão de Fábrica)</th>
                                    <th className="py-3 px-4 text-right">Quantidade</th>
                                    <th className="py-3 px-4 text-right">Preço Unit.</th>
                                    <th className="py-3 px-4 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {order.items.map((it) => (
                                    <tr key={it.id} className="hover:bg-slate-800/40 transition">
                                        <td className="py-3 px-4">
                                            <div className="font-bold text-white">{it.item.name}</div>
                                            <div className="text-[10px] text-slate-500 font-mono">SKU: {it.item.sku}</div>
                                        </td>
                                        <td className="py-3 px-4">
                                            {it.bom ? (
                                                <span className="inline-flex items-center gap-1.5 text-indigo-400 font-mono">
                                                    <Cpu className="w-3.5 h-3.5" />
                                                    {it.bom.code} (v{it.bom.version})
                                                </span>
                                            ) : (
                                                <span className="text-slate-500 text-[11px]">Pronta Entrega / Revenda</span>
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-center">
                                            {it.production_order ? (
                                                <Link
                                                    href={`/app/production/orders/${it.production_order.id}`}
                                                    className="inline-flex items-center gap-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/30 text-indigo-400 px-3 py-1 rounded-lg font-mono text-xs font-semibold transition"
                                                >
                                                    <Factory className="w-3.5 h-3.5" />
                                                    <span>{it.production_order.order_number}</span>
                                                    <ArrowUpRight className="w-3 h-3" />
                                                </Link>
                                            ) : (
                                                <span className="text-slate-500 text-[11px]">Sem OP disparada</span>
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-right font-mono text-white">
                                            {parseFloat(it.quantity).toFixed(2)} {it.item.unit_of_measure?.code || 'UN'}
                                        </td>
                                        <td className="py-3 px-4 text-right font-mono text-slate-300">
                                            {formatCurrency(it.unit_price)}
                                        </td>
                                        <td className="py-3 px-4 text-right font-mono font-bold text-white text-sm">
                                            {formatCurrency(it.total_price)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </TenantDesktopLayout>
    );
}
