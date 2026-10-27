import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import CommercialSubNav from '@/Pages/CRM/Components/CommercialSubNav';
import { PaginatedResponse } from '@/types';
import { 
    ShoppingCart, 
    Search, 
    ArrowRight, 
    Calendar, 
    Clock, 
    DollarSign, 
    Building2,
    Factory,
    CheckCircle2
} from 'lucide-react';

interface Partner {
    id: string;
    name: string;
    trade_name?: string;
    document_number: string;
}

interface Quote {
    id: string;
    quote_number: string;
}

interface SalesOrder {
    id: string;
    order_number: string;
    partner_id: string;
    quote_id?: string;
    status: 'CONFIRMED' | 'IN_PRODUCTION' | 'SHIPPED' | 'COMPLETED' | 'CANCELLED';
    total_amount: string;
    delivery_date?: string;
    payment_terms?: string;
    partner?: Partner;
    quote?: Quote;
    items_count?: number;
    created_at: string;
}

interface Props {
    orders: PaginatedResponse<SalesOrder>;
    filters: {
        search?: string;
        status?: string;
    };
}

export default function SalesOrdersIndex({ orders, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');

    const formatCurrency = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(isNaN(num) ? 0 : num);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/app/sales/orders', { search, status }, { preserveState: true });
    };

    const handleStatusFilter = (newStatus: string) => {
        setStatus(newStatus);
        router.get('/app/sales/orders', { search, status: newStatus }, { preserveState: true });
    };

    const getStatusBadge = (st: string) => {
        switch (st) {
            case 'CONFIRMED':
                return <span className="px-2 py-0.5 text-xs font-semibold rounded bg-blue-500/10 text-blue-400 border border-blue-500/20">Confirmado</span>;
            case 'IN_PRODUCTION':
                return <span className="px-2 py-0.5 text-xs font-semibold rounded bg-purple-500/10 text-purple-400 border border-purple-500/20">Em Produção</span>;
            case 'SHIPPED':
                return <span className="px-2 py-0.5 text-xs font-semibold rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">Expedido</span>;
            case 'COMPLETED':
                return <span className="px-2 py-0.5 text-xs font-bold rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Concluído</span>;
            default:
                return <span className="px-2 py-0.5 text-xs font-semibold rounded bg-rose-500/10 text-rose-400 border border-rose-500/20">Cancelado</span>;
        }
    };

    return (
        <TenantDesktopLayout title="Pedidos de Venda">
            <Head title="Pedidos de Venda - Comercial" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <CommercialSubNav />

                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-xl font-bold text-white flex items-center gap-2">
                            <ShoppingCart className="w-6 h-6 text-indigo-400" />
                            Pedidos de Venda
                        </h1>
                        <p className="text-xs text-slate-400 mt-1">
                            Acompanhamento de pedidos confirmados, expedição e ordens de produção disparadas.
                        </p>
                    </div>
                </div>

                {/* Filtros */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
                    <form onSubmit={handleSearch} className="flex-1 w-full flex items-center gap-2">
                        <div className="relative flex-1">
                            <Search className="w-4 h-4 text-slate-500 absolute left-3 top-3" />
                            <input
                                type="text"
                                placeholder="Buscar por número do pedido ou cliente..."
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
                            { key: 'CONFIRMED', label: 'Confirmados' },
                            { key: 'IN_PRODUCTION', label: 'Em Produção' },
                            { key: 'SHIPPED', label: 'Expedidos' },
                            { key: 'COMPLETED', label: 'Concluídos' },
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

                {/* Tabela de Pedidos */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs text-slate-300">
                            <thead className="bg-slate-950/70 border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th className="py-3 px-4">Pedido</th>
                                    <th className="py-3 px-4">Cliente</th>
                                    <th className="py-3 px-4">Origem</th>
                                    <th className="py-3 px-4 text-right">Valor Total</th>
                                    <th className="py-3 px-4 text-center">Entrega</th>
                                    <th className="py-3 px-4 text-center">Status</th>
                                    <th className="py-3 px-4 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {orders.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="py-12 text-center text-slate-500">
                                            Nenhum pedido de venda encontrado com os filtros aplicados.
                                        </td>
                                    </tr>
                                ) : (
                                    orders.data.map((order) => (
                                        <tr key={order.id} className="hover:bg-slate-800/40 transition">
                                            <td className="py-3 px-4">
                                                <div className="font-mono font-bold text-indigo-400">{order.order_number}</div>
                                                <div className="text-[10px] text-slate-500">
                                                    {order.items_count || 1} {order.items_count === 1 ? 'item' : 'itens'}
                                                </div>
                                            </td>
                                            <td className="py-3 px-4">
                                                <div className="font-bold text-white">{order.partner?.name || 'Cliente'}</div>
                                                <div className="text-[11px] text-slate-400 font-mono">{order.partner?.document_number}</div>
                                            </td>
                                            <td className="py-3 px-4">
                                                {order.quote ? (
                                                    <span className="font-mono text-slate-300 text-[11px]">
                                                        {order.quote.quote_number}
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-500">Manual</span>
                                                )}
                                            </td>
                                            <td className="py-3 px-4 text-right">
                                                <span className="font-bold text-white font-mono text-sm">
                                                    {formatCurrency(order.total_amount)}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-center">
                                                <span className="text-slate-300">
                                                    {order.delivery_date ? new Date(order.delivery_date).toLocaleDateString('pt-BR') : '—'}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-center">
                                                {getStatusBadge(order.status)}
                                            </td>
                                            <td className="py-3 px-4 text-right">
                                                <Link
                                                    href={`/app/sales/orders/${order.id}`}
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
        </TenantDesktopLayout>
    );
}
