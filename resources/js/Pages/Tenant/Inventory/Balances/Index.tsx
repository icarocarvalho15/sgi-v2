import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../../../Layouts/AppLayout';
import { 
    Boxes, 
    Search, 
    Filter, 
    ArrowUpDown, 
    PlusCircle, 
    FileSpreadsheet, 
    AlertTriangle,
    CheckCircle2,
    Building2,
    DollarSign,
    Download
} from 'lucide-react';
import { exportInventoryBalancesToCsv } from '../../../../Utils/exportToCsv';

interface Warehouse {
    id: string;
    name: string;
    code: string;
}

interface Category {
    id: string;
    name: string;
    code: string;
}

interface InventoryBalance {
    id: string;
    item_id: string;
    warehouse_id: string;
    quantity_on_hand: string;
    quantity_reserved: string;
    average_cost: string;
    total_value: string;
    last_movement_at: string | null;
    item: {
        id: string;
        code: string;
        name: string;
        unit_of_measure: string;
        min_stock: string;
        category?: {
            id: string;
            name: string;
        };
    };
    warehouse: {
        id: string;
        name: string;
        code: string;
    };
}

interface Props {
    balances: {
        data: InventoryBalance[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    warehouses: Warehouse[];
    categories: Category[];
    filters: {
        warehouse_id?: string;
        category_id?: string;
        search?: string;
    };
    metrics: {
        total_stock_value: string;
        total_items_count: number;
    };
}

export default function BalancesIndex({ balances, warehouses, categories, filters, metrics }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [warehouseId, setWarehouseId] = useState(filters.warehouse_id || '');
    const [categoryId, setCategoryId] = useState(filters.category_id || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/inventory/balances', {
            search: search || undefined,
            warehouse_id: warehouseId || undefined,
            category_id: categoryId || undefined,
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

    return (
        <AppLayout title="Estoque & Saldos em Tempo Real">
            <Head title="Controle de Estoque & Saldos - SGI v2.0" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                            <Boxes className="w-7 h-7 text-indigo-400" />
                            Controle de Suprimentos & Estoque
                        </h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Saldos em tempo real com valorização pelo Custo Médio Ponderado (CMP) e razão imutável.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => exportInventoryBalancesToCsv(balances.data)}
                            className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-colors shadow-sm"
                            title="Exportar saldos de estoque em formato CSV compatível com Excel"
                        >
                            <Download className="w-3.5 h-3.5 text-indigo-400" />
                            Exportar Planilha (CSV)
                        </button>
                        <Link
                            href="/finance?tab=kardex"
                            className="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-medium border border-slate-700 transition-colors shadow-sm"
                        >
                            <FileSpreadsheet className="w-4 h-4 text-emerald-400" />
                            Extrato Kardex
                        </Link>
                        <Link
                            href="/inventory/adjustments/create"
                            className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition-colors shadow-lg shadow-indigo-600/20"
                        >
                            <PlusCircle className="w-4 h-4" />
                            Lançar Ajuste / Entrada
                        </Link>
                    </div>
                </div>

                {/* Metrics Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Valor Total do Estoque
                            </span>
                            <div className="p-2 rounded-lg bg-emerald-500/10 text-emerald-400">
                                <DollarSign className="w-5 h-5" />
                            </div>
                        </div>
                        <div className="text-2xl font-bold text-white mt-2">
                            {formatCurrency(metrics.total_stock_value)}
                        </div>
                        <span className="text-xs text-emerald-400 font-medium mt-1 inline-block">
                            Custo Médio Ponderado (Zero Float)
                        </span>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Itens com Saldo Físico
                            </span>
                            <div className="p-2 rounded-lg bg-indigo-500/10 text-indigo-400">
                                <Boxes className="w-5 h-5" />
                            </div>
                        </div>
                        <div className="text-2xl font-bold text-white mt-2">
                            {metrics.total_items_count}
                        </div>
                        <span className="text-xs text-slate-400 font-medium mt-1 inline-block">
                            Posições de estoque registradas
                        </span>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Almoxarifados Ativos
                            </span>
                            <div className="p-2 rounded-lg bg-sky-500/10 text-sky-400">
                                <Building2 className="w-5 h-5" />
                            </div>
                        </div>
                        <div className="text-2xl font-bold text-white mt-2">
                            {warehouses.length}
                        </div>
                        <span className="text-xs text-sky-400 font-medium mt-1 inline-block">
                            Depósitos isolados por RLS
                        </span>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-sm">
                    <form onSubmit={handleSearch} className="flex flex-col md:flex-row gap-3">
                        <div className="relative flex-1">
                            <Search className="w-4 h-4 absolute left-3 top-3 text-slate-500" />
                            <input
                                type="text"
                                placeholder="Buscar por código SKU ou nome do insumo/papel/chapa..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-9 pr-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>

                        <select
                            value={warehouseId}
                            onChange={(e) => setWarehouseId(e.target.value)}
                            className="bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Todos os Depósitos</option>
                            {warehouses.map((wh) => (
                                <option key={wh.id} value={wh.id}>
                                    {wh.name} ({wh.code})
                                </option>
                            ))}
                        </select>

                        <select
                            value={categoryId}
                            onChange={(e) => setCategoryId(e.target.value)}
                            className="bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Todas as Categorias</option>
                            {categories.map((cat) => (
                                <option key={cat.id} value={cat.id}>
                                    {cat.name}
                                </option>
                            ))}
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
                                    <th className="py-3 px-4">SKU / Insumo</th>
                                    <th className="py-3 px-4">Depósito</th>
                                    <th className="py-3 px-4 text-right">Saldo Físico</th>
                                    <th className="py-3 px-4 text-right">Empenhado</th>
                                    <th className="py-3 px-4 text-right">Disponível</th>
                                    <th className="py-3 px-4 text-right">Custo Médio (CMP)</th>
                                    <th className="py-3 px-4 text-right">Valor Total</th>
                                    <th className="py-3 px-4 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {balances.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="py-12 text-center text-slate-500">
                                            Nenhum saldo de estoque encontrado para os filtros selecionados.
                                        </td>
                                    </tr>
                                ) : (
                                    balances.data.map((b) => {
                                        const qtyOnHand = parseFloat(b.quantity_on_hand || '0');
                                        const qtyReserved = parseFloat(b.quantity_reserved || '0');
                                        const available = qtyOnHand - qtyReserved;
                                        const minStock = parseFloat(b.item?.min_stock || '0');
                                        const isLowStock = minStock > 0 && available < minStock;

                                        return (
                                            <tr key={b.id} className="hover:bg-slate-800/30 transition-colors">
                                                <td className="py-3.5 px-4">
                                                    <div className="font-medium text-slate-200">
                                                        {b.item?.name}
                                                    </div>
                                                    <div className="text-xs text-slate-400 flex items-center gap-2 mt-0.5">
                                                        <span className="font-mono text-indigo-400">{b.item?.code}</span>
                                                        {b.item?.category && (
                                                            <span>• {b.item.category.name}</span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="py-3.5 px-4 text-slate-300">
                                                    <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs bg-slate-800 border border-slate-700 text-slate-300">
                                                        <Building2 className="w-3 h-3 text-slate-400" />
                                                        {b.warehouse?.name}
                                                    </span>
                                                </td>
                                                <td className="py-3.5 px-4 text-right font-mono font-medium text-slate-200">
                                                    {formatDecimal(b.quantity_on_hand)} <span className="text-xs text-slate-400">{b.item?.unit_of_measure}</span>
                                                </td>
                                                <td className="py-3.5 px-4 text-right font-mono text-amber-400">
                                                    {formatDecimal(b.quantity_reserved)} <span className="text-xs text-slate-400">{b.item?.unit_of_measure}</span>
                                                </td>
                                                <td className="py-3.5 px-4 text-right">
                                                    <span className={`font-mono font-semibold ${isLowStock ? 'text-rose-400' : 'text-emerald-400'}`}>
                                                        {formatDecimal(available)} <span className="text-xs text-slate-400">{b.item?.unit_of_measure}</span>
                                                    </span>
                                                    {isLowStock && (
                                                        <div className="text-[10px] text-rose-400 flex items-center justify-end gap-1 mt-0.5">
                                                            <AlertTriangle className="w-3 h-3" /> Abaixo do mín ({formatDecimal(minStock)})
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="py-3.5 px-4 text-right font-mono text-slate-300">
                                                    {formatCurrency(b.average_cost)}
                                                </td>
                                                <td className="py-3.5 px-4 text-right font-mono font-semibold text-white">
                                                    {formatCurrency(b.total_value)}
                                                </td>
                                                <td className="py-3.5 px-4 text-center">
                                                    <Link
                                                        href={`/inventory/kardex?item_id=${b.item_id}&warehouse_id=${b.warehouse_id}`}
                                                        className="inline-flex items-center gap-1 text-xs text-indigo-400 hover:text-indigo-300 hover:underline"
                                                    >
                                                        <FileSpreadsheet className="w-3.5 h-3.5" />
                                                        Ver Kardex
                                                    </Link>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {balances.last_page > 1 && (
                        <div className="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                            <div>
                                Mostrando página {balances.current_page} de {balances.last_page} ({balances.total} saldos)
                            </div>
                            <div className="flex gap-1">
                                {balances.links.map((link, idx) => (
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
