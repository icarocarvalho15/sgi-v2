import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import { InventoryBalance, Warehouse, PaginatedResponse } from '@/types';
import StockAdjustmentModal from '../Components/StockAdjustmentModal';
import InventorySubNav from '../Components/InventorySubNav';
import { 
    Boxes, 
    Warehouse as WarehouseIcon, 
    Search, 
    AlertTriangle, 
    ArrowUpDown, 
    Coins, 
    SlidersHorizontal,
    TrendingDown,
    Clock
} from 'lucide-react';

interface Props {
    balances: PaginatedResponse<InventoryBalance>;
    warehouses: Warehouse[];
    filters: {
        warehouse_id?: string;
        search?: string;
        low_stock?: boolean;
    };
}

export default function BalancesIndex({ balances, warehouses, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedWarehouse, setSelectedWarehouse] = useState(filters.warehouse_id || '');
    const [lowStock, setLowStock] = useState(filters.low_stock || false);

    // Modal de Ajuste de Estoque
    const [adjustmentModalOpen, setAdjustmentModalOpen] = useState(false);
    const [selectedForAdjustment, setSelectedForAdjustment] = useState<{
        warehouseId?: string;
        itemId?: string;
    }>({});

    const handleFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/app/inventory/balances', {
            warehouse_id: selectedWarehouse,
            search,
            low_stock: lowStock ? '1' : '',
        }, { preserveState: true });
    };

    const handleOpenAdjustment = (warehouseId?: string, itemId?: string) => {
        setSelectedForAdjustment({ warehouseId, itemId });
        setAdjustmentModalOpen(true);
    };

    // Prepara lista simplificada de itens a partir dos saldos para o modal
    const itemsList = balances.data.map((b) => ({
        id: b.item_id,
        sku: b.item?.sku || '',
        name: b.item?.name || '',
        unit_of_measure: b.item?.unit_of_measure,
    })).filter((v, i, a) => a.findIndex(t => t.id === v.id) === i);

    return (
        <TenantDesktopLayout title="Saldos de Estoque por Almoxarifado">
            <Head title="Posição de Estoque - SGI v2.0" />

            <div className="space-y-6">
                <InventorySubNav />

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center space-x-2 text-indigo-400 text-xs font-semibold uppercase tracking-wider mb-1">
                            <WarehouseIcon className="w-4 h-4" />
                            <span>Controle de Estoque & Depósitos</span>
                        </div>
                        <h1 className="text-2xl font-black text-white">Posição & Saldos de Estoque</h1>
                        <p className="text-sm text-slate-400">
                            Quantidades físicas em estoque, reservas e valorização por Custo Médio (CMP)
                        </p>
                    </div>

                    <div className="flex items-center space-x-3">
                        <button
                            onClick={() => handleOpenAdjustment()}
                            className="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold px-4 py-2.5 rounded-lg flex items-center space-x-2 shadow-lg shadow-emerald-950/50 transition cursor-pointer"
                        >
                            <SlidersHorizontal className="w-4 h-4" />
                            <span>Lançar Ajuste Manual</span>
                        </button>
                    </div>
                </div>

                {/* Filtros */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <form onSubmit={handleFilterSubmit} className="flex flex-col sm:flex-row items-center gap-3">
                        <div className="relative flex-1 w-full">
                            <Search className="w-4 h-4 absolute left-3.5 top-3 text-slate-400" />
                            <input
                                type="text"
                                placeholder="Filtrar por SKU ou descrição do produto..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full bg-slate-950 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <select
                            value={selectedWarehouse}
                            onChange={(e) => {
                                setSelectedWarehouse(e.target.value);
                                router.get('/app/inventory/balances', {
                                    warehouse_id: e.target.value,
                                    search,
                                    low_stock: lowStock ? '1' : '',
                                }, { preserveState: true });
                            }}
                            className="bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-indigo-500 w-full sm:w-auto"
                        >
                            <option value="">Todos os Almoxarifados</option>
                            {warehouses.map((w) => (
                                <option key={w.id} value={w.id}>
                                    [{w.code}] {w.name}
                                </option>
                            ))}
                        </select>

                        <label className="flex items-center space-x-2 text-xs font-semibold text-amber-400 bg-amber-500/10 border border-amber-500/20 px-3 py-2 rounded-lg cursor-pointer shrink-0">
                            <input
                                type="checkbox"
                                checked={lowStock}
                                onChange={(e) => {
                                    setLowStock(e.target.checked);
                                    router.get('/app/inventory/balances', {
                                        warehouse_id: selectedWarehouse,
                                        search,
                                        low_stock: e.target.checked ? '1' : '',
                                    }, { preserveState: true });
                                }}
                                className="rounded border-amber-600 bg-slate-900 text-amber-500 focus:ring-0"
                            />
                            <span>Estoque Crítico (Abaixo do Mínimo)</span>
                        </label>

                        <button
                            type="submit"
                            className="bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition shrink-0"
                        >
                            Buscar
                        </button>
                    </form>
                </div>

                {/* Tabela de Saldos */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-slate-800 bg-slate-950/60 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                    <th className="py-3 px-4">Almoxarifado</th>
                                    <th className="py-3 px-4">SKU / Item</th>
                                    <th className="py-3 px-4 text-center">UM</th>
                                    <th className="py-3 px-4 text-right">Saldo Físico</th>
                                    <th className="py-3 px-4 text-right">Reservado</th>
                                    <th className="py-3 px-4 text-right">Custo Médio (CMP)</th>
                                    <th className="py-3 px-4 text-right">Valor em Estoque</th>
                                    <th className="py-3 px-4 text-center">Último Movimento</th>
                                    <th className="py-3 px-4 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800 text-xs">
                                {balances.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={9} className="py-12 text-center text-slate-500">
                                            <Boxes className="w-8 h-8 mx-auto mb-2 opacity-40" />
                                            <p className="text-sm font-semibold">Nenhum saldo registrado nos almoxarifados.</p>
                                        </td>
                                    </tr>
                                ) : (
                                    balances.data.map((b) => {
                                        const qty = Number(b.quantity_on_hand);
                                        const minStock = Number(b.item?.min_stock || 0);
                                        const isCritical = minStock > 0 && qty <= minStock;
                                        const totalVal = qty * Number(b.average_cost);

                                        return (
                                            <tr key={b.id} className="hover:bg-slate-800/40 transition">
                                                <td className="py-3 px-4">
                                                    <div className="font-semibold text-slate-200">
                                                        {b.warehouse?.name || 'Almoxarifado'}
                                                    </div>
                                                    <span className="text-[10px] font-mono text-slate-400">
                                                        [{b.warehouse?.code}]
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <div className="font-mono font-bold text-indigo-400">
                                                        {b.item?.sku}
                                                    </div>
                                                    <div className="text-white font-medium truncate max-w-xs">
                                                        {b.item?.name}
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4 text-center font-mono font-semibold text-slate-300">
                                                    {b.item?.unit_of_measure?.symbol || 'UN'}
                                                </td>
                                                <td className="py-3 px-4 text-right font-mono font-bold">
                                                    <span className={isCritical ? 'text-rose-400 flex items-center justify-end space-x-1' : 'text-emerald-400'}>
                                                        {isCritical && <AlertTriangle className="w-3.5 h-3.5 mr-1" />}
                                                        {qty.toLocaleString('pt-BR', { minimumFractionDigits: 4 })}
                                                    </span>
                                                    {isCritical && (
                                                        <span className="text-[10px] text-rose-400 block font-sans">
                                                            Mín: {minStock.toLocaleString('pt-BR')}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="py-3 px-4 text-right font-mono text-slate-400">
                                                    {Number(b.quantity_reserved).toLocaleString('pt-BR', { minimumFractionDigits: 4 })}
                                                </td>
                                                <td className="py-3 px-4 text-right font-mono font-semibold text-slate-200">
                                                    R$ {Number(b.average_cost).toLocaleString('pt-BR', { minimumFractionDigits: 4 })}
                                                </td>
                                                <td className="py-3 px-4 text-right font-mono font-bold text-emerald-400">
                                                    R$ {totalVal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                                <td className="py-3 px-4 text-center text-slate-400 text-[11px]">
                                                    {b.last_movement_at ? (
                                                        <span className="flex items-center justify-center space-x-1">
                                                            <Clock className="w-3 h-3 text-slate-500" />
                                                            <span>{new Date(b.last_movement_at).toLocaleDateString('pt-BR')}</span>
                                                        </span>
                                                    ) : (
                                                        '-'
                                                    )}
                                                </td>
                                                <td className="py-3 px-4 text-right">
                                                    <button
                                                        onClick={() => handleOpenAdjustment(b.warehouse_id, b.item_id)}
                                                        className="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 hover:text-white rounded-lg border border-slate-700 transition"
                                                    >
                                                        Ajustar
                                                    </button>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Paginação */}
                    {balances.links && balances.links.length > 3 && (
                        <div className="p-4 border-t border-slate-800 flex items-center justify-between">
                            <span className="text-xs text-slate-400">
                                Mostrando {balances.from || 0} a {balances.to || 0} de {balances.total} saldos
                            </span>
                            <div className="flex space-x-1">
                                {balances.links.map((link, i) => (
                                    <button
                                        key={i}
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                        className={`px-3 py-1 rounded text-xs transition ${
                                            link.active
                                                ? 'bg-indigo-600 text-white font-bold'
                                                : link.url
                                                ? 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white'
                                                : 'text-slate-600 cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal de Ajuste de Estoque */}
            <StockAdjustmentModal
                isOpen={adjustmentModalOpen}
                onClose={() => setAdjustmentModalOpen(false)}
                warehouses={warehouses}
                items={itemsList}
                initialWarehouseId={selectedForAdjustment.warehouseId}
                initialItemId={selectedForAdjustment.itemId}
            />
        </TenantDesktopLayout>
    );
}
