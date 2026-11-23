import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '../../../../Layouts/AppLayout';
import { Sliders, Building, Calendar, CheckCircle2, AlertCircle, ChevronRight, Play, Download } from 'lucide-react';
import { exportProductionOrdersToCsv } from '../../../../Utils/exportToCsv';

interface ProductionOrder {
    id: string;
    order_number: string;
    product_name: string;
    target_quantity: number;
    produced_quantity: number;
    due_date: string | null;
    status?: {
        id: string;
        name: string;
        color: string;
    };
    sales_order?: {
        customer?: {
            name: string;
        };
    };
}

interface Status {
    id: string;
    name: string;
    color: string;
}

interface Props {
    orders: {
        data: ProductionOrder[];
        links: any[];
        total: number;
    };
    statuses: Status[];
}

export default function Index({ orders, statuses }: Props) {
    const [selectedStatus, setSelectedStatus] = useState('');

    const handleFilter = (statusId: string) => {
        setSelectedStatus(statusId);
        router.get('/production/orders', statusId ? { status_id: statusId } : {}, { preserveState: true });
    };

    return (
        <AppLayout title="Chão de Fábrica & Ordens de Produção (PCP)">
            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div>
                        <p className="text-sm text-slate-400">
                            Acompanhamento em tempo real das etapas produtivas da gráfica: CTP, Impressão, Guilhotina e Expedição.
                        </p>
                    </div>

                    <div className="flex items-center gap-2.5">
                        <button
                            type="button"
                            onClick={() => exportProductionOrdersToCsv(orders.data)}
                            className="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-xl text-xs font-semibold transition-colors shadow-sm"
                            title="Exportar ordens de produção em formato CSV (Excel)"
                        >
                            <Download className="w-3.5 h-3.5 text-indigo-400" />
                            Exportar Planilha (CSV)
                        </button>

                        <select
                            value={selectedStatus}
                            onChange={(e) => handleFilter(e.target.value)}
                            className="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Todos os Estágios Produtivos</option>
                            {statuses.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-300">
                            <thead className="bg-slate-800/40 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                                <tr>
                                    <th className="px-6 py-4">O.P.</th>
                                    <th className="px-6 py-4">Produto & Cliente</th>
                                    <th className="px-6 py-4">Estágio de Produção</th>
                                    <th className="px-6 py-4">Progresso da Tiragem</th>
                                    <th className="px-6 py-4">Prazo de Entrega</th>
                                    <th className="px-6 py-4 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {orders.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-12 text-center text-slate-500">
                                            Nenhuma ordem de produção em andamento.
                                        </td>
                                    </tr>
                                ) : (
                                    orders.data.map((order) => {
                                        const progressPercent = Math.min(
                                            100,
                                            Math.round((order.produced_quantity / Math.max(1, order.target_quantity)) * 100)
                                        );

                                        return (
                                            <tr key={order.id} className="hover:bg-slate-800/30 transition-colors">
                                                <td className="px-6 py-4 font-mono text-xs font-bold text-indigo-400">
                                                    {order.order_number}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="font-semibold text-slate-200 block">
                                                        {order.product_name}
                                                    </span>
                                                    {order.sales_order?.customer && (
                                                        <div className="flex items-center gap-1.5 text-xs text-slate-400 mt-0.5">
                                                            <Building className="w-3.5 h-3.5 text-slate-500 shrink-0" />
                                                            <span>{order.sales_order.customer.name}</span>
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4">
                                                    {order.status && (
                                                        <span
                                                            className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
                                                            style={{
                                                                color: order.status.color,
                                                                borderColor: `${order.status.color}40`,
                                                                backgroundColor: `${order.status.color}15`,
                                                            }}
                                                        >
                                                            <span
                                                                className="w-1.5 h-1.5 rounded-full"
                                                                style={{ backgroundColor: order.status.color }}
                                                            />
                                                            {order.status.name}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="space-y-1.5 w-40">
                                                        <div className="flex justify-between text-xs font-mono">
                                                            <span className="text-slate-300">
                                                                {order.produced_quantity} / {order.target_quantity} un
                                                            </span>
                                                            <span className="text-indigo-400 font-semibold">
                                                                {progressPercent}%
                                                            </span>
                                                        </div>
                                                        <div className="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                                            <div
                                                                className="bg-indigo-500 h-1.5 rounded-full transition-all duration-500"
                                                                style={{ width: `${progressPercent}%` }}
                                                            />
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-xs text-slate-400">
                                                    {order.due_date ? (
                                                        <div className="flex items-center gap-1.5">
                                                            <Calendar className="w-3.5 h-3.5 text-slate-500" />
                                                            <span>{order.due_date}</span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-slate-600">—</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <Link
                                                        href={`/production/orders/${order.id}`}
                                                        className="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg transition-colors border border-slate-700/60"
                                                    >
                                                        <Play className="w-3 h-3 text-emerald-400" />
                                                        Chão de Fábrica
                                                    </Link>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
