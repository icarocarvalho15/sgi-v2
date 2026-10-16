import React from 'react';
import { useForm } from '@inertiajs/react';
import { Warehouse } from '@/types';
import { X, ArrowDownRight, ArrowUpRight, AlertCircle } from 'lucide-react';

interface Props {
    isOpen: boolean;
    onClose: () => void;
    warehouses: Warehouse[];
    items: Array<{ id: string; sku: string; name: string; unit_of_measure?: { symbol: string } }>;
    initialWarehouseId?: string;
    initialItemId?: string;
}

export default function StockAdjustmentModal({
    isOpen,
    onClose,
    warehouses,
    items,
    initialWarehouseId,
    initialItemId,
}: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        warehouse_id: initialWarehouseId || (warehouses[0]?.id ?? ''),
        item_id: initialItemId || (items[0]?.id ?? ''),
        direction: 'IN' as 'IN' | 'OUT',
        quantity: '',
        unit_cost: '',
        justification: '',
    });

    React.useEffect(() => {
        if (initialWarehouseId) setData('warehouse_id', initialWarehouseId);
        if (initialItemId) setData('item_id', initialItemId);
    }, [initialWarehouseId, initialItemId]);

    if (!isOpen) return null;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/app/inventory/adjustments', {
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in">
            <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full overflow-hidden shadow-2xl">
                {/* Header */}
                <div className="flex items-center justify-between p-5 border-b border-slate-800 bg-slate-900/60">
                    <div className="flex items-center space-x-2.5">
                        <div className={`p-2 rounded-lg ${data.direction === 'IN' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'}`}>
                            {data.direction === 'IN' ? <ArrowUpRight className="w-5 h-5" /> : <ArrowDownRight className="w-5 h-5" />}
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-white">Lançamento de Ajuste no Kardex</h3>
                            <p className="text-xs text-slate-400">Movimentação imutável auditada</p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-5 space-y-4">
                    {/* Direção: Entrada (+) ou Saída (-) */}
                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1.5 uppercase tracking-wider">
                            Tipo de Movimento
                        </label>
                        <div className="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                onClick={() => setData('direction', 'IN')}
                                className={`py-2.5 px-4 rounded-lg font-semibold text-xs flex items-center justify-center space-x-2 border transition ${
                                    data.direction === 'IN'
                                        ? 'bg-emerald-500/20 border-emerald-500 text-emerald-300 shadow-sm'
                                        : 'bg-slate-800/40 border-slate-700 text-slate-400 hover:bg-slate-800'
                                }`}
                            >
                                <ArrowUpRight className="w-4 h-4" />
                                <span>Entrada de Inventário (+)</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => setData('direction', 'OUT')}
                                className={`py-2.5 px-4 rounded-lg font-semibold text-xs flex items-center justify-center space-x-2 border transition ${
                                    data.direction === 'OUT'
                                        ? 'bg-rose-500/20 border-rose-500 text-rose-300 shadow-sm'
                                        : 'bg-slate-800/40 border-slate-700 text-slate-400 hover:bg-slate-800'
                                }`}
                            >
                                <ArrowDownRight className="w-4 h-4" />
                                <span>Saída de Inventário (-)</span>
                            </button>
                        </div>
                    </div>

                    {/* Almoxarifado */}
                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">
                            Almoxarifado / Depósito <span className="text-rose-400">*</span>
                        </label>
                        <select
                            value={data.warehouse_id}
                            onChange={(e) => setData('warehouse_id', e.target.value)}
                            className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                        >
                            {warehouses.map((w) => (
                                <option key={w.id} value={w.id}>
                                    [{w.code}] {w.name}
                                </option>
                            ))}
                        </select>
                        {errors.warehouse_id && (
                            <p className="text-xs text-rose-400 mt-1">{errors.warehouse_id}</p>
                        )}
                    </div>

                    {/* Item */}
                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">
                            Item / Insumo <span className="text-rose-400">*</span>
                        </label>
                        <select
                            value={data.item_id}
                            onChange={(e) => setData('item_id', e.target.value)}
                            className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                        >
                            {items.map((i) => (
                                <option key={i.id} value={i.id}>
                                    {i.sku} — {i.name} {i.unit_of_measure ? `(${i.unit_of_measure.symbol})` : ''}
                                </option>
                            ))}
                        </select>
                        {errors.item_id && <p className="text-xs text-rose-400 mt-1">{errors.item_id}</p>}
                    </div>

                    {/* Quantidade e Custo Unitário */}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">
                                Quantidade <span className="text-rose-400">*</span>
                            </label>
                            <input
                                type="number"
                                step="0.0001"
                                placeholder="0.0000"
                                value={data.quantity}
                                onChange={(e) => setData('quantity', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                required
                            />
                            {errors.quantity && <p className="text-xs text-rose-400 mt-1">{errors.quantity}</p>}
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">
                                Custo Unitário (R$)
                            </label>
                            <input
                                type="number"
                                step="0.0001"
                                placeholder="Opcional (CMP)"
                                value={data.unit_cost}
                                onChange={(e) => setData('unit_cost', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                            />
                            {errors.unit_cost && <p className="text-xs text-rose-400 mt-1">{errors.unit_cost}</p>}
                        </div>
                    </div>

                    {/* Justificativa Obrigatória */}
                    <div>
                        <div className="flex items-center justify-between mb-1">
                            <label className="block text-xs font-semibold text-slate-300">
                                Justificativa do Ajuste <span className="text-rose-400">*</span>
                            </label>
                            <span className="text-[10px] text-slate-400">Mínimo 5 caracteres</span>
                        </div>
                        <textarea
                            rows={3}
                            placeholder="Descreva o motivo deste ajuste (ex: Divergência de contagem de inventário físico anual)..."
                            value={data.justification}
                            onChange={(e) => setData('justification', e.target.value)}
                            className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                            required
                        />
                        {errors.justification && (
                            <p className="text-xs text-rose-400 mt-1">{errors.justification}</p>
                        )}
                    </div>

                    <div className="p-3 bg-amber-500/10 border border-amber-500/20 rounded-lg flex items-start space-x-2 text-xs text-amber-300">
                        <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
                        <span>
                            Aviso: O Kardex é imutável. Uma vez lançado, este movimento não poderá ser editado ou excluído.
                        </span>
                    </div>

                    {/* Footer */}
                    <div className="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 rounded-lg text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className={`px-5 py-2 rounded-lg text-sm font-bold text-white shadow-lg transition flex items-center space-x-2 ${
                                data.direction === 'IN'
                                    ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-950/40'
                                    : 'bg-rose-600 hover:bg-rose-500 shadow-rose-950/40'
                            }`}
                        >
                            <span>Confirmar Lançamento</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
