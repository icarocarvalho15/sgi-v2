import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../../../Layouts/AppLayout';
import { 
    FileSpreadsheet, 
    ArrowDownLeft, 
    ArrowUpRight, 
    Boxes, 
    PlusCircle, 
    Search, 
    Filter,
    ShieldCheck,
    Building2,
    Calendar,
    Tag,
    Download
} from 'lucide-react';
import { exportKardexToCsv } from '../../../../Utils/exportToCsv';

interface ItemOption {
    id: string;
    name: string;
    code: string;
    unit_of_measure: string;
}

interface WarehouseOption {
    id: string;
    name: string;
    code: string;
}

interface KardexMovement {
    id: string;
    movement_type: string;
    direction: 'in' | 'out';
    quantity: string;
    unit_cost: string;
    total_cost: string;
    balance_quantity_after: string;
    balance_average_cost_after: string;
    balance_total_value_after: string;
    reference_type: string | null;
    reference_id: string | null;
    notes: string | null;
    created_at: string;
    item: {
        id: string;
        name: string;
        code: string;
        unit_of_measure: string;
    };
    warehouse: {
        id: string;
        name: string;
        code: string;
    };
}

interface Props {
    movements: {
        data: KardexMovement[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    items: ItemOption[];
    warehouses: WarehouseOption[];
    selectedItem: ItemOption | null;
    filters: {
        item_id?: string;
        warehouse_id?: string;
        movement_type?: string;
    };
}

export default function KardexIndex({ movements, items, warehouses, selectedItem, filters }: Props) {
    const [itemId, setItemId] = useState(filters.item_id || '');
    const [warehouseId, setWarehouseId] = useState(filters.warehouse_id || '');
    const [movementType, setMovementType] = useState(filters.movement_type || '');

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/inventory/kardex', {
            item_id: itemId || undefined,
            warehouse_id: warehouseId || undefined,
            movement_type: movementType || undefined,
        }, { preserveState: true });
    };

    const formatCurrency = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num || 0);
    };

    const formatDecimal = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 4 }).format(num || 0);
    };

    const formatDate = (dateStr: string) => {
        return new Intl.DateTimeFormat('pt-BR', {
            dateStyle: 'short',
            timeStyle: 'medium',
        }).format(new Date(dateStr));
    };

    const movementTypeLabels: Record<string, { label: string; color: string }> = {
        purchase_receipt: { label: 'Entrada de Compra', color: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' },
        production_consumption: { label: 'Consumo OP', color: 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' },
        production_return: { label: 'Devolução de OP', color: 'bg-sky-500/10 text-sky-400 border-sky-500/20' },
        inventory_adjustment: { label: 'Ajuste de Inventário', color: 'bg-amber-500/10 text-amber-400 border-amber-500/20' },
        transfer_in: { label: 'Transferência (Entrada)', color: 'bg-teal-500/10 text-teal-400 border-teal-500/20' },
        transfer_out: { label: 'Transferência (Saída)', color: 'bg-purple-500/10 text-purple-400 border-purple-500/20' },
        sales_dispatch: { label: 'Expedição de Venda', color: 'bg-rose-500/10 text-rose-400 border-rose-500/20' },
    };

    return (
        <AppLayout title="Razão Imutável de Kardex">
            <Head title="Extrato Kardex - SGI v2.0" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                                <FileSpreadsheet className="w-7 h-7 text-emerald-400" />
                                Razão Geral de Kardex
                            </h1>
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <ShieldCheck className="w-3.5 h-3.5" />
                                Imutável (Append-Only)
                            </span>
                        </div>
                        <p className="text-sm text-slate-400 mt-1">
                            Trilha contábil e de suprimentos com registro histórico do Custo Médio Ponderado a cada movimento.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => exportKardexToCsv(movements.data)}
                            className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-colors shadow-sm"
                            title="Exportar movimentações Kardex em formato CSV compatível com Excel"
                        >
                            <Download className="w-3.5 h-3.5 text-emerald-400" />
                            Exportar Planilha (CSV)
                        </button>
                        <Link
                            href="/inventory/balances"
                            className="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-medium border border-slate-700 transition-colors"
                        >
                            <Boxes className="w-4 h-4 text-indigo-400" />
                            Saldos em Estoque
                        </Link>
                        <Link
                            href="/inventory/adjustments/create"
                            className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition-colors shadow-lg shadow-indigo-600/20"
                        >
                            <PlusCircle className="w-4 h-4" />
                            Novo Ajuste
                        </Link>
                    </div>
                </div>

                {/* Filter Form */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-sm">
                    <form onSubmit={handleFilter} className="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">Insumo / Produto</label>
                            <select
                                value={itemId}
                                onChange={(e) => setItemId(e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Todos os Insumos</option>
                                {items.map((it) => (
                                    <option key={it.id} value={it.id}>
                                        {it.name} ({it.code})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">Almoxarifado</label>
                            <select
                                value={warehouseId}
                                onChange={(e) => setWarehouseId(e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Todos os Depósitos</option>
                                {warehouses.map((wh) => (
                                    <option key={wh.id} value={wh.id}>
                                        {wh.name} ({wh.code})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">Tipo de Movimentação</label>
                            <select
                                value={movementType}
                                onChange={(e) => setMovementType(e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Todos os Tipos</option>
                                <option value="purchase_receipt">Entrada de Compra</option>
                                <option value="production_consumption">Consumo de OP</option>
                                <option value="production_return">Devolução de OP</option>
                                <option value="inventory_adjustment">Ajuste de Inventário</option>
                                <option value="sales_dispatch">Expedição de Venda</option>
                            </select>
                        </div>

                        <div className="flex items-end">
                            <button
                                type="submit"
                                className="w-full py-2 px-4 bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-medium rounded-lg border border-slate-700 transition-colors flex items-center justify-center gap-2"
                            >
                                <Filter className="w-4 h-4" />
                                Filtrar Extrato
                            </button>
                        </div>
                    </form>
                </div>

                {/* Selected Item Detail Banner if filtered */}
                {selectedItem && (
                    <div className="bg-indigo-950/40 border border-indigo-800/60 rounded-xl p-4 flex items-center justify-between">
                        <div>
                            <span className="text-xs uppercase font-semibold text-indigo-400 tracking-wider">
                                Filtrando Extrato por Insumo
                            </span>
                            <h2 className="text-lg font-bold text-white mt-0.5">
                                {selectedItem.name} <span className="font-mono text-indigo-300">({selectedItem.code})</span>
                            </h2>
                        </div>
                        <Link
                            href="/inventory/kardex"
                            className="text-xs text-indigo-300 hover:text-white underline"
                        >
                            Limpar filtro de insumo
                        </Link>
                    </div>
                )}

                {/* Ledger Table */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr className="border-b border-slate-800 bg-slate-950/50 text-slate-400 text-xs uppercase font-semibold">
                                    <th className="py-3 px-4">Data / Hora</th>
                                    <th className="py-3 px-4">Tipo / Referência</th>
                                    <th className="py-3 px-4">Insumo</th>
                                    <th className="py-3 px-4">Depósito</th>
                                    <th className="py-3 px-4 text-right">Movimentado</th>
                                    <th className="py-3 px-4 text-right">Custo Unitário</th>
                                    <th className="py-3 px-4 text-right">Total Operação</th>
                                    <th className="py-3 px-4 text-right">Saldo Pós-Movimento</th>
                                    <th className="py-3 px-4 text-right">Novo CMP</th>
                                    <th className="py-3 px-4 text-right">Valor Pós-Movimento</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60 font-mono text-xs">
                                {movements.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={10} className="py-12 text-center text-slate-500 font-sans text-sm">
                                            Nenhuma movimentação registrada no Kardex para os filtros aplicados.
                                        </td>
                                    </tr>
                                ) : (
                                    movements.data.map((m) => {
                                        const isIn = m.direction === 'in';
                                        const typeBadge = movementTypeLabels[m.movement_type] || {
                                            label: m.movement_type,
                                            color: 'bg-slate-800 text-slate-300 border-slate-700',
                                        };

                                        return (
                                            <tr key={m.id} className="hover:bg-slate-800/30 transition-colors">
                                                <td className="py-3.5 px-4 text-slate-400 whitespace-nowrap font-sans text-xs">
                                                    {formatDate(m.created_at)}
                                                </td>
                                                <td className="py-3.5 px-4 font-sans">
                                                    <span className={`inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium border ${typeBadge.color}`}>
                                                        {typeBadge.label}
                                                    </span>
                                                    {m.notes && (
                                                        <div className="text-[11px] text-slate-400 truncate max-w-xs mt-0.5">
                                                            {m.notes}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="py-3.5 px-4 font-sans">
                                                    <div className="font-medium text-slate-200">
                                                        {m.item?.name}
                                                    </div>
                                                    <div className="text-xs text-indigo-400 font-mono">
                                                        {m.item?.code}
                                                    </div>
                                                </td>
                                                <td className="py-3.5 px-4 font-sans text-slate-300">
                                                    {m.warehouse?.name}
                                                </td>
                                                <td className="py-3.5 px-4 text-right">
                                                    <span className={`inline-flex items-center gap-1 font-bold ${isIn ? 'text-emerald-400' : 'text-rose-400'}`}>
                                                        {isIn ? <ArrowDownLeft className="w-3.5 h-3.5" /> : <ArrowUpRight className="w-3.5 h-3.5" />}
                                                        {isIn ? '+' : '-'}{formatDecimal(m.quantity)}
                                                    </span>
                                                    <span className="text-[10px] text-slate-500 ml-1">
                                                        {m.item?.unit_of_measure}
                                                    </span>
                                                </td>
                                                <td className="py-3.5 px-4 text-right text-slate-300">
                                                    {formatCurrency(m.unit_cost)}
                                                </td>
                                                <td className="py-3.5 px-4 text-right font-medium text-slate-200">
                                                    {formatCurrency(m.total_cost)}
                                                </td>
                                                <td className="py-3.5 px-4 text-right font-bold text-slate-100 bg-slate-950/20">
                                                    {formatDecimal(m.balance_quantity_after)} {m.item?.unit_of_measure}
                                                </td>
                                                <td className="py-3.5 px-4 text-right text-indigo-300 bg-slate-950/20">
                                                    {formatCurrency(m.balance_average_cost_after)}
                                                </td>
                                                <td className="py-3.5 px-4 text-right font-bold text-white bg-slate-950/20">
                                                    {formatCurrency(m.balance_total_value_after)}
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {movements.last_page > 1 && (
                        <div className="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400 font-sans">
                            <div>
                                Mostrando página {movements.current_page} de {movements.last_page} ({movements.total} lançamentos)
                            </div>
                            <div className="flex gap-1">
                                {movements.links.map((link, idx) => (
                                    <Link
                                        key={idx}
                                        href={link.url || '#'}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-2.5 py-1 rounded border text-xs ${
                                            link.active
                                                ? 'bg-indigo-600 text-white border-indigo-600'
                                                : 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700'
                                        } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
