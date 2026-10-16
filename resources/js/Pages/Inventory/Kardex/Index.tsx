import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import { KardexMovement, Warehouse, PaginatedResponse } from '@/types';
import StockAdjustmentModal from '../Components/StockAdjustmentModal';
import InventorySubNav from '../Components/InventorySubNav';
import { 
    FileSpreadsheet, 
    ArrowUpRight, 
    ArrowDownRight, 
    Filter, 
    SlidersHorizontal, 
    User as UserIcon,
    Calendar,
    Clock,
    Boxes
} from 'lucide-react';

interface Props {
    movements: PaginatedResponse<KardexMovement>;
    items: Array<{ id: string; sku: string; name: string }>;
    warehouses: Warehouse[];
    filters: {
        item_id?: string;
        warehouse_id?: string;
        movement_type?: string;
    };
}

export default function KardexIndex({ movements, items, warehouses, filters }: Props) {
    const [selectedItem, setSelectedItem] = useState(filters.item_id || '');
    const [selectedWarehouse, setSelectedWarehouse] = useState(filters.warehouse_id || '');
    const [selectedMovementType, setSelectedMovementType] = useState(filters.movement_type || '');
    const [adjustmentModalOpen, setAdjustmentModalOpen] = useState(false);

    const handleFilterChange = (newItemId: string, newWarehouseId: string, newType: string) => {
        router.get('/app/inventory/kardex', {
            item_id: newItemId,
            warehouse_id: newWarehouseId,
            movement_type: newType,
        }, { preserveState: true });
    };

    const getMovementBadge = (type: string) => {
        const isInbound = type.startsWith('IN_');
        const color = isInbound
            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
            : 'bg-rose-500/10 text-rose-400 border-rose-500/20';
        const Icon = isInbound ? ArrowUpRight : ArrowDownRight;

        const labels: Record<string, string> = {
            IN_PURCHASE: 'Entrada por Compra',
            IN_PRODUCTION: 'Entrada por Produção (PA)',
            IN_ADJUSTMENT: 'Ajuste de Estoque (+)',
            IN_TRANSFER: 'Transferência Recebida',
            OUT_PRODUCTION: 'Consumo em OP',
            OUT_SALE: 'Saída por Venda',
            OUT_ADJUSTMENT: 'Ajuste de Estoque (-)',
            OUT_TRANSFER: 'Transferência Enviada',
            OUT_SCRAP: 'Perda / Sucata',
        };

        return (
            <span className={`inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[10px] font-bold border ${color}`}>
                <Icon className="w-3 h-3" />
                <span>{labels[type] || type}</span>
            </span>
        );
    };

    return (
        <TenantDesktopLayout title="Extrato do Kardex (Razão Imutável)">
            <Head title="Extrato do Kardex - SGI v2.0" />

            <div className="space-y-6">
                <InventorySubNav />

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center space-x-2 text-indigo-400 text-xs font-semibold uppercase tracking-wider mb-1">
                            <FileSpreadsheet className="w-4 h-4" />
                            <span>Auditoria e Rastreabilidade</span>
                        </div>
                        <h1 className="text-2xl font-black text-white">Extrato do Kardex (Razão Imutável)</h1>
                        <p className="text-sm text-slate-400">
                            Registro contínuo e à prova de adulteração de todas as movimentações e evolução do Custo Médio
                        </p>
                    </div>

                    <div className="flex items-center space-x-3">
                        <button
                            onClick={() => setAdjustmentModalOpen(true)}
                            className="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold px-4 py-2.5 rounded-lg flex items-center space-x-2 shadow-lg shadow-emerald-950/50 transition cursor-pointer"
                        >
                            <SlidersHorizontal className="w-4 h-4" />
                            <span>Lançar Ajuste Manual</span>
                        </button>
                    </div>
                </div>

                {/* Filtros */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                                Filtrar por Item
                            </label>
                            <select
                                value={selectedItem}
                                onChange={(e) => {
                                    setSelectedItem(e.target.value);
                                    handleFilterChange(e.target.value, selectedWarehouse, selectedMovementType);
                                }}
                                className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Todos os Itens</option>
                                {items.map((i) => (
                                    <option key={i.id} value={i.id}>
                                        {i.sku} — {i.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                                Almoxarifado
                            </label>
                            <select
                                value={selectedWarehouse}
                                onChange={(e) => {
                                    setSelectedWarehouse(e.target.value);
                                    handleFilterChange(selectedItem, e.target.value, selectedMovementType);
                                }}
                                className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Todos os Almoxarifados</option>
                                {warehouses.map((w) => (
                                    <option key={w.id} value={w.id}>
                                        [{w.code}] {w.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                                Tipo de Movimentação
                            </label>
                            <select
                                value={selectedMovementType}
                                onChange={(e) => {
                                    setSelectedMovementType(e.target.value);
                                    handleFilterChange(selectedItem, selectedWarehouse, e.target.value);
                                }}
                                className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Todos os Tipos</option>
                                <option value="IN_PURCHASE">Entrada por Compra</option>
                                <option value="IN_PRODUCTION">Entrada por Produção (PA)</option>
                                <option value="IN_ADJUSTMENT">Ajuste de Estoque (+)</option>
                                <option value="OUT_PRODUCTION">Consumo em OP</option>
                                <option value="OUT_SALE">Saída por Venda</option>
                                <option value="OUT_ADJUSTMENT">Ajuste de Estoque (-)</option>
                                <option value="OUT_SCRAP">Perda / Sucata</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Tabela do Razão Imutável */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-slate-800 bg-slate-950/60 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                    <th className="py-3 px-4">Data / Hora</th>
                                    <th className="py-3 px-4">Nº Movimento</th>
                                    <th className="py-3 px-4">Tipo</th>
                                    <th className="py-3 px-4">Almoxarifado</th>
                                    <th className="py-3 px-4">Item (SKU)</th>
                                    <th className="py-3 px-4 text-right">Qtd Movimentada</th>
                                    <th className="py-3 px-4 text-right">Custo Unit.</th>
                                    <th className="py-3 px-4 text-right">Evolução do Saldo</th>
                                    <th className="py-3 px-4 text-right">CMP Resultante</th>
                                    <th className="py-3 px-4">Justificativa / Resp.</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800 text-xs font-mono">
                                {movements.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={10} className="py-12 text-center text-slate-500 font-sans">
                                            <Boxes className="w-8 h-8 mx-auto mb-2 opacity-40" />
                                            <p className="text-sm font-semibold">Nenhuma movimentação de Kardex registrada.</p>
                                        </td>
                                    </tr>
                                ) : (
                                    movements.data.map((m) => {
                                        const isInbound = m.movement_type.startsWith('IN_');
                                        const sign = isInbound ? '+' : '-';
                                        const qtyColor = isInbound ? 'text-emerald-400' : 'text-rose-400';

                                        return (
                                            <tr key={m.id} className="hover:bg-slate-800/40 transition">
                                                <td className="py-3 px-4 text-slate-400 text-[11px] whitespace-nowrap">
                                                    {new Date(m.created_at).toLocaleString('pt-BR')}
                                                </td>
                                                <td className="py-3 px-4 font-bold text-indigo-400 whitespace-nowrap">
                                                    {m.movement_number}
                                                </td>
                                                <td className="py-3 px-4 font-sans whitespace-nowrap">
                                                    {getMovementBadge(m.movement_type)}
                                                </td>
                                                <td className="py-3 px-4 font-sans text-slate-300">
                                                    {m.warehouse?.name || 'Almoxarifado'}
                                                </td>
                                                <td className="py-3 px-4 font-sans">
                                                    <span className="font-mono font-bold text-white block">
                                                        {m.item?.sku}
                                                    </span>
                                                    <span className="text-[10px] text-slate-400 truncate block max-w-xs">
                                                        {m.item?.name}
                                                    </span>
                                                </td>
                                                <td className={`py-3 px-4 text-right font-bold ${qtyColor} whitespace-nowrap`}>
                                                    {sign} {Number(m.quantity).toLocaleString('pt-BR', { minimumFractionDigits: 4 })}
                                                </td>
                                                <td className="py-3 px-4 text-right text-slate-300 whitespace-nowrap">
                                                    R$ {Number(m.unit_cost).toLocaleString('pt-BR', { minimumFractionDigits: 4 })}
                                                </td>
                                                <td className="py-3 px-4 text-right text-slate-300 whitespace-nowrap">
                                                    <span className="text-slate-500">
                                                        {Number(m.previous_balance).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                                                    </span>
                                                    <span className="text-slate-400 mx-1">→</span>
                                                    <span className="font-bold text-white">
                                                        {Number(m.resulting_balance).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4 text-right font-bold text-emerald-400 whitespace-nowrap">
                                                    R$ {Number(m.resulting_average_cost).toLocaleString('pt-BR', { minimumFractionDigits: 4 })}
                                                </td>
                                                <td className="py-3 px-4 font-sans text-[11px] text-slate-400 max-w-xs">
                                                    {m.justification && (
                                                        <div className="truncate text-slate-300 italic" title={m.justification}>
                                                            "{m.justification}"
                                                        </div>
                                                    )}
                                                    {m.user && (
                                                        <div className="text-[10px] text-slate-500 flex items-center space-x-1 mt-0.5">
                                                            <UserIcon className="w-3 h-3" />
                                                            <span>{m.user.name}</span>
                                                        </div>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Paginação */}
                    {movements.links && movements.links.length > 3 && (
                        <div className="p-4 border-t border-slate-800 flex items-center justify-between">
                            <span className="text-xs text-slate-400">
                                Mostrando {movements.from || 0} a {movements.to || 0} de {movements.total} lançamentos
                            </span>
                            <div className="flex space-x-1">
                                {movements.links.map((link, i) => (
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

            {/* Modal de Ajuste */}
            <StockAdjustmentModal
                isOpen={adjustmentModalOpen}
                onClose={() => setAdjustmentModalOpen(false)}
                warehouses={warehouses}
                items={items}
            />
        </TenantDesktopLayout>
    );
}
