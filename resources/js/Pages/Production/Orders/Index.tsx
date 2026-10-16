import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import ProductionSubNav from '../Components/ProductionSubNav';
import { PaginatedResponse, Item, Warehouse } from '@/types';
import { 
    Factory, 
    Plus, 
    Play, 
    CheckCircle2, 
    Clock, 
    AlertTriangle, 
    X, 
    ArrowUpRight,
    Layers,
    Warehouse as WarehouseIcon
} from 'lucide-react';

interface Bom {
    id: string;
    code: string;
    version: number;
    item_id: string;
    item?: Item;
}

interface ProductionOrder {
    id: string;
    order_number: string;
    item_id: string;
    bom_id: string;
    warehouse_id: string;
    materials_warehouse_id: string;
    planned_quantity: string;
    produced_quantity: string;
    scrapped_quantity: string;
    status: 'DRAFT' | 'RELEASED' | 'IN_PROGRESS' | 'COMPLETED' | 'CANCELLED';
    priority: 'LOW' | 'MEDIUM' | 'HIGH' | 'URGENT';
    due_date: string | null;
    created_at: string;
    item?: Item;
    bom?: Bom;
    warehouse?: Warehouse;
    materials_warehouse?: Warehouse;
}

interface Props {
    orders: PaginatedResponse<ProductionOrder>;
    manufacturedItems: Item[];
    boms: Bom[];
    warehouses: Warehouse[];
    filters: {
        search: string;
        status: string;
    };
}

export default function ProductionOrdersIndex({
    orders,
    manufacturedItems,
    boms,
    warehouses,
    filters,
}: Props) {
    const [modalOpen, setModalOpen] = useState(false);
    const [releasingId, setReleasingId] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        item_id: manufacturedItems[0]?.id || '',
        bom_id: boms[0]?.id || '',
        warehouse_id: warehouses[0]?.id || '',
        materials_warehouse_id: warehouses[0]?.id || '',
        planned_quantity: '10.0000',
        priority: 'MEDIUM',
        due_date: '',
        notes: '',
    });

    const handleItemChange = (itemId: string) => {
        setData('item_id', itemId);
        const matchingBom = boms.find((b) => b.item_id === itemId);
        if (matchingBom) {
            setData('bom_id', matchingBom.id);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/app/production/orders', {
            onSuccess: () => {
                reset();
                setModalOpen(false);
            },
        });
    };

    const handleRelease = (orderId: string) => {
        setReleasingId(orderId);
        router.post(`/app/production/orders/${orderId}/release`, {}, {
            onFinish: () => setReleasingId(null),
        });
    };

    const getStatusBadge = (status: ProductionOrder['status']) => {
        switch (status) {
            case 'DRAFT':
                return <span className="bg-slate-800 text-slate-300 px-2 py-0.5 rounded text-xs font-semibold">Rascunho</span>;
            case 'RELEASED':
                return <span className="bg-sky-500/15 text-sky-400 border border-sky-500/30 px-2 py-0.5 rounded text-xs font-semibold">Liberada</span>;
            case 'IN_PROGRESS':
                return <span className="bg-amber-500/15 text-amber-400 border border-amber-500/30 px-2 py-0.5 rounded text-xs font-semibold animate-pulse">Em Produção</span>;
            case 'COMPLETED':
                return <span className="bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded text-xs font-semibold">Concluída</span>;
            case 'CANCELLED':
                return <span className="bg-rose-500/15 text-rose-400 border border-rose-500/30 px-2 py-0.5 rounded text-xs font-semibold">Cancelada</span>;
        }
    };

    return (
        <TenantDesktopLayout title="Ordens de Produção">
            <Head title="Ordens de Produção (OPs) - SGI v2.0" />

            <div className="space-y-6">
                <ProductionSubNav />

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center space-x-2 text-indigo-400 text-xs font-semibold uppercase tracking-wider mb-1">
                            <Factory className="w-4 h-4" />
                            <span>PCP & Manufatura</span>
                        </div>
                        <h1 className="text-2xl font-black text-white">Ordens de Produção (OPs)</h1>
                        <p className="text-sm text-slate-400">
                            Planejamento, empenho de matérias-primas e acompanhamento de ordens industriais
                        </p>
                    </div>

                    <button
                        onClick={() => {
                            reset();
                            setModalOpen(true);
                        }}
                        className="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold px-4 py-2.5 rounded-lg flex items-center space-x-2 shadow-lg shadow-indigo-950/50 transition cursor-pointer"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Nova Ordem de Produção</span>
                    </button>
                </div>

                {/* Tabela de OPs */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-300">
                            <thead className="bg-slate-950/70 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th className="py-3 px-4">Número da OP</th>
                                    <th className="py-3 px-4">Item a Fabricar</th>
                                    <th className="py-3 px-4">Ficha Técnica (BOM)</th>
                                    <th className="py-3 px-4 text-right">Progresso (Produzido / Previsto)</th>
                                    <th className="py-3 px-4 text-center">Status</th>
                                    <th className="py-3 px-4 text-center">Prioridade</th>
                                    <th className="py-3 px-4 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {orders.data.map((op) => {
                                    const progressPercent = Number(op.planned_quantity) > 0
                                        ? Math.min(100, (Number(op.produced_quantity) / Number(op.planned_quantity)) * 100)
                                        : 0;

                                    return (
                                        <tr key={op.id} className="hover:bg-slate-800/40 transition">
                                            <td className="py-3 px-4">
                                                <div className="font-mono font-bold text-white flex items-center space-x-1.5">
                                                    <span>{op.order_number}</span>
                                                </div>
                                            </td>
                                            <td className="py-3 px-4">
                                                <div>
                                                    <div className="font-bold text-slate-100">{op.item?.name}</div>
                                                    <div className="text-xs font-mono text-slate-400">{op.item?.sku}</div>
                                                </div>
                                            </td>
                                            <td className="py-3 px-4">
                                                <span className="font-mono text-xs bg-slate-800 text-slate-300 px-2 py-0.5 rounded">
                                                    {op.bom?.code || 'BOM Padrão'}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-right">
                                                <div className="font-mono font-semibold text-slate-200">
                                                    {Number(op.produced_quantity).toFixed(2)} / {Number(op.planned_quantity).toFixed(2)}
                                                </div>
                                                <div className="w-full bg-slate-800 rounded-full h-1.5 mt-1 overflow-hidden">
                                                    <div
                                                        className={`h-1.5 rounded-full ${
                                                            progressPercent >= 100 ? 'bg-emerald-500' : 'bg-indigo-500'
                                                        }`}
                                                        style={{ width: `${progressPercent}%` }}
                                                    ></div>
                                                </div>
                                            </td>
                                            <td className="py-3 px-4 text-center">
                                                {getStatusBadge(op.status)}
                                            </td>
                                            <td className="py-3 px-4 text-center">
                                                <span className="text-xs font-semibold text-slate-400 uppercase">
                                                    {op.priority}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-center">
                                                <div className="flex items-center justify-center space-x-2">
                                                    {op.status === 'DRAFT' && (
                                                        <button
                                                            onClick={() => handleRelease(op.id)}
                                                            disabled={releasingId === op.id}
                                                            title="Liberar OP para Chão de Fábrica"
                                                            className="inline-flex items-center space-x-1 text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white px-2.5 py-1 rounded transition"
                                                        >
                                                            <Play className="w-3 h-3" />
                                                            <span>Liberar</span>
                                                        </button>
                                                    )}
                                                    <Link
                                                        href={`/app/production/orders/${op.id}`}
                                                        className="inline-flex items-center space-x-1 text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 px-2.5 py-1 rounded transition"
                                                    >
                                                        <span>Detalhes</span>
                                                        <ArrowUpRight className="w-3 h-3" />
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Modal de Nova OP */}
            {modalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full overflow-hidden shadow-2xl">
                        <div className="flex items-center justify-between p-5 border-b border-slate-800 bg-slate-900/60">
                            <div>
                                <h3 className="text-base font-bold text-white">Criar Ordem de Produção</h3>
                                <p className="text-xs text-slate-400">Geração de lote fabril com empenho de insumos</p>
                            </div>
                            <button
                                onClick={() => setModalOpen(false)}
                                className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="p-5 space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Item / Produto a Fabricar <span className="text-rose-400">*</span>
                                </label>
                                <select
                                    value={data.item_id}
                                    onChange={(e) => handleItemChange(e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    required
                                >
                                    {manufacturedItems.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            [{item.sku}] {item.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.item_id && <p className="text-xs text-rose-400 mt-1">{errors.item_id}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Ficha Técnica (BOM) Utilizada <span className="text-rose-400">*</span>
                                </label>
                                <select
                                    value={data.bom_id}
                                    onChange={(e) => setData('bom_id', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                    required
                                >
                                    {boms.map((bom) => (
                                        <option key={bom.id} value={bom.id}>
                                            {bom.code} (v{bom.version})
                                        </option>
                                    ))}
                                </select>
                                {errors.bom_id && <p className="text-xs text-rose-400 mt-1">{errors.bom_id}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Depósito Origem Insumos <span className="text-rose-400">*</span>
                                    </label>
                                    <select
                                        value={data.materials_warehouse_id}
                                        onChange={(e) => setData('materials_warehouse_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                        required
                                    >
                                        {warehouses.map((w) => (
                                            <option key={w.id} value={w.id}>
                                                [{w.code}] {w.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Depósito Destino Produto <span className="text-rose-400">*</span>
                                    </label>
                                    <select
                                        value={data.warehouse_id}
                                        onChange={(e) => setData('warehouse_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                        required
                                    >
                                        {warehouses.map((w) => (
                                            <option key={w.id} value={w.id}>
                                                [{w.code}] {w.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Quantidade Planejada <span className="text-rose-400">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        step="0.0001"
                                        value={data.planned_quantity}
                                        onChange={(e) => setData('planned_quantity', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                        required
                                    />
                                    {errors.planned_quantity && <p className="text-xs text-rose-400 mt-1">{errors.planned_quantity}</p>}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Prioridade Fabril
                                    </label>
                                    <select
                                        value={data.priority}
                                        onChange={(e) => setData('priority', e.target.value as any)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="LOW">Baixa</option>
                                        <option value="MEDIUM">Média</option>
                                        <option value="HIGH">Alta</option>
                                        <option value="URGENT">Urgente</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Observações da OP
                                </label>
                                <textarea
                                    rows={2}
                                    placeholder="Ex: Pedido especial do cliente Alfa..."
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div className="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setModalOpen(false)}
                                    className="px-4 py-2 rounded-lg text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold px-5 py-2 rounded-lg shadow-lg shadow-indigo-950/50 transition cursor-pointer"
                                >
                                    Salvar OP em Rascunho
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </TenantDesktopLayout>
    );
}
