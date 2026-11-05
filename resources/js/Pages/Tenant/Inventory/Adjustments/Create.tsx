import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '../../../../Layouts/AppLayout';
import { 
    Boxes, 
    ArrowLeft, 
    ArrowDownLeft, 
    ArrowUpRight, 
    CheckCircle2, 
    AlertCircle,
    Info
} from 'lucide-react';

interface ItemOption {
    id: string;
    name: string;
    code: string;
    unit_of_measure: string;
    standard_cost: string;
}

interface WarehouseOption {
    id: string;
    name: string;
    code: string;
}

interface Props {
    items: ItemOption[];
    warehouses: WarehouseOption[];
}

export default function AdjustmentsCreate({ items, warehouses }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        item_id: items[0]?.id || '',
        warehouse_id: warehouses[0]?.id || '',
        direction: 'in',
        quantity: '',
        unit_cost: '',
        notes: '',
    });

    const selectedItem = items.find((i) => i.id === data.item_id);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/inventory/adjustments');
    };

    return (
        <AppLayout title="Lançar Ajuste Manual de Estoque">
            <Head title="Ajuste de Estoque - SGI v2.0" />

            <div className="max-w-3xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <Link
                            href="/inventory/balances"
                            className="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-200 transition-colors mb-2"
                        >
                            <ArrowLeft className="w-3.5 h-3.5" />
                            Voltar para Saldos em Estoque
                        </Link>
                        <h1 className="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                            <Boxes className="w-7 h-7 text-indigo-400" />
                            Lançar Ajuste / Movimentação Manual
                        </h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Lançamentos manuais impactam diretamente o Kardex e o Custo Médio Ponderado.
                        </p>
                    </div>
                </div>

                {/* Form Card */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm">
                    <form onSubmit={handleSubmit} className="space-y-5">
                        {/* Direction Switch */}
                        <div>
                            <label className="block text-sm font-medium text-slate-300 mb-2">
                                Sentido da Movimentação
                            </label>
                            <div className="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    onClick={() => setData('direction', 'in')}
                                    className={`py-3 px-4 rounded-xl border text-sm font-medium flex items-center justify-center gap-2 transition-all ${
                                        data.direction === 'in'
                                            ? 'bg-emerald-600/15 border-emerald-500 text-emerald-400 ring-1 ring-emerald-500'
                                            : 'bg-slate-950 border-slate-800 text-slate-400 hover:bg-slate-800/50'
                                    }`}
                                >
                                    <ArrowDownLeft className="w-4 h-4 text-emerald-400" />
                                    Entrada no Estoque (Sobra / Inventário)
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setData('direction', 'out')}
                                    className={`py-3 px-4 rounded-xl border text-sm font-medium flex items-center justify-center gap-2 transition-all ${
                                        data.direction === 'out'
                                            ? 'bg-rose-600/15 border-rose-500 text-rose-400 ring-1 ring-rose-500'
                                            : 'bg-slate-950 border-slate-800 text-slate-400 hover:bg-slate-800/50'
                                    }`}
                                >
                                    <ArrowUpRight className="w-4 h-4 text-rose-400" />
                                    Saída de Estoque (Avaria / Quebra / Perda)
                                </button>
                            </div>
                            {errors.direction && (
                                <p className="text-xs text-rose-400 mt-1">{errors.direction}</p>
                            )}
                        </div>

                        {/* Item and Warehouse */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Insumo / Produto Gráfico *
                                </label>
                                <select
                                    value={data.item_id}
                                    onChange={(e) => {
                                        const itId = e.target.value;
                                        const it = items.find((i) => i.id === itId);
                                        setData((prev) => ({
                                            ...prev,
                                            item_id: itId,
                                            unit_cost: it ? it.standard_cost : prev.unit_cost,
                                        }));
                                    }}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                >
                                    {items.map((it) => (
                                        <option key={it.id} value={it.id}>
                                            {it.name} ({it.code}) - {it.unit_of_measure}
                                        </option>
                                    ))}
                                </select>
                                {errors.item_id && (
                                    <p className="text-xs text-rose-400 mt-1">{errors.item_id}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Depósito / Almoxarifado *
                                </label>
                                <select
                                    value={data.warehouse_id}
                                    onChange={(e) => setData('warehouse_id', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                >
                                    {warehouses.map((wh) => (
                                        <option key={wh.id} value={wh.id}>
                                            {wh.name} ({wh.code})
                                        </option>
                                    ))}
                                </select>
                                {errors.warehouse_id && (
                                    <p className="text-xs text-rose-400 mt-1">{errors.warehouse_id}</p>
                                )}
                            </div>
                        </div>

                        {/* Quantity and Unit Cost */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Quantidade a Movimentar * ({selectedItem?.unit_of_measure || 'UN'})
                                </label>
                                <input
                                    type="number"
                                    step="0.0001"
                                    min="0.0001"
                                    placeholder="Ex: 50.0000"
                                    value={data.quantity}
                                    onChange={(e) => setData('quantity', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-indigo-500"
                                />
                                {errors.quantity && (
                                    <p className="text-xs text-rose-400 mt-1">{errors.quantity}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    {data.direction === 'in' ? 'Custo Unitário da Entrada (R$) *' : 'Custo Unitário (R$)'}
                                </label>
                                <input
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    placeholder={data.direction === 'in' ? 'Ex: 12.50' : 'Automático pelo CMP atual'}
                                    disabled={data.direction === 'out'}
                                    value={data.unit_cost}
                                    onChange={(e) => setData('unit_cost', e.target.value)}
                                    className={`w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-indigo-500 ${
                                        data.direction === 'out' ? 'opacity-50 cursor-not-allowed bg-slate-900' : ''
                                    }`}
                                />
                                {errors.unit_cost && (
                                    <p className="text-xs text-rose-400 mt-1">{errors.unit_cost}</p>
                                )}
                                {data.direction === 'out' && (
                                    <span className="text-[11px] text-slate-500 mt-1 block">
                                        Em saídas, o custo é fixado automaticamente pelo CMP atual do depósito.
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Notes / Reason */}
                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">
                                Justificativa / Observação do Lançamento *
                            </label>
                            <textarea
                                rows={3}
                                placeholder="Ex: Inventário físico rotativo do mês; ou avaria de 3 resmas durante transporte."
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-indigo-500"
                            />
                            {errors.notes && (
                                <p className="text-xs text-rose-400 mt-1">{errors.notes}</p>
                            )}
                        </div>

                        {/* Notice */}
                        <div className="bg-slate-950/60 rounded-lg p-3 border border-slate-800 flex items-start gap-2.5 text-xs text-slate-400">
                            <Info className="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" />
                            <span>
                                Este lançamento é gravado de forma <strong>definitiva e imutável</strong> no Kardex. Em caso de erro, efetue um contra-lançamento com a devida justificativa.
                            </span>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                            <Link
                                href="/inventory/balances"
                                className="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium transition-colors"
                            >
                                Cancelar
                            </Link>

                            <button
                                type="submit"
                                disabled={processing}
                                className="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-sm font-medium transition-colors shadow-lg shadow-indigo-600/20"
                            >
                                {processing ? 'Gravando no Kardex...' : 'Confirmar Lançamento'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
