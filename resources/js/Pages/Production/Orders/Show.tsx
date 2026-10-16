import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import ProductionSubNav from '../Components/ProductionSubNav';
import { Item, Warehouse, UnitOfMeasure } from '@/types';
import { 
    Factory, 
    ArrowLeft, 
    Play, 
    CheckCircle2, 
    Layers, 
    Clock, 
    Plus, 
    X,
    FileSpreadsheet,
    Warehouse as WarehouseIcon,
    AlertCircle
} from 'lucide-react';

interface WorkCenter {
    id: string;
    code: string;
    name: string;
}

interface Bom {
    id: string;
    code: string;
    version: number;
}

interface Material {
    id: string;
    item: Item;
    unit_of_measure: UnitOfMeasure;
    planned_quantity: string;
    consumed_quantity: string;
    unit_cost: string;
    total_cost: string;
}

interface Operation {
    id: string;
    sequence: number;
    status: 'PENDING' | 'IN_PROGRESS' | 'PAUSED' | 'COMPLETED';
    executed_time_seconds: number;
    started_at: string | null;
    completed_at: string | null;
    work_center: WorkCenter;
    operator?: { name: string } | null;
}

interface ProductionOrder {
    id: string;
    order_number: string;
    status: 'DRAFT' | 'RELEASED' | 'IN_PROGRESS' | 'COMPLETED' | 'CANCELLED';
    priority: string;
    planned_quantity: string;
    produced_quantity: string;
    scrapped_quantity: string;
    start_date: string | null;
    due_date: string | null;
    notes: string | null;
    item: Item;
    bom: Bom;
    warehouse: Warehouse;
    materials_warehouse: Warehouse;
    materials: Material[];
    operations: Operation[];
}

interface Props {
    order: ProductionOrder;
}

export default function ProductionOrdersShow({ order }: Props) {
    const [appointmentModalOpen, setAppointmentModalOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        produced_quantity: (Math.max(0, Number(order.planned_quantity) - Number(order.produced_quantity))).toFixed(4),
        scrapped_quantity: '0.0000',
        notes: '',
    });

    const handleRelease = () => {
        router.post(`/app/production/orders/${order.id}/release`);
    };

    const handleAppointmentSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/app/production/orders/${order.id}/record-production`, {
            onSuccess: () => {
                reset();
                setAppointmentModalOpen(false);
            },
        });
    };

    const formatSeconds = (sec: number) => {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return `${m}m ${s}s`;
    };

    return (
        <TenantDesktopLayout title={`OP ${order.order_number}`}>
            <Head title={`OP ${order.order_number} - SGI v2.0`} />

            <div className="space-y-6">
                <ProductionSubNav />

                {/* Topbar com navegação de retorno */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center space-x-3">
                        <Link
                            href="/app/production/orders"
                            className="p-2 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <div className="flex items-center space-x-2">
                                <h1 className="text-2xl font-black text-white font-mono">{order.order_number}</h1>
                                <span className="bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 text-xs px-2.5 py-0.5 rounded font-semibold uppercase">
                                    {order.status}
                                </span>
                            </div>
                            <p className="text-sm text-slate-400">
                                Fabricação de <strong>{order.item?.name}</strong> [{order.item?.sku}]
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center space-x-3">
                        {order.status === 'DRAFT' && (
                            <button
                                onClick={handleRelease}
                                className="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold px-4 py-2 rounded-lg flex items-center space-x-2 shadow-lg shadow-emerald-950/50 transition cursor-pointer"
                            >
                                <Play className="w-4 h-4" />
                                <span>Liberar para Chão de Fábrica</span>
                            </button>
                        )}

                        {['RELEASED', 'IN_PROGRESS'].includes(order.status) && (
                            <button
                                onClick={() => setAppointmentModalOpen(true)}
                                className="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold px-4 py-2 rounded-lg flex items-center space-x-2 shadow-lg shadow-indigo-950/50 transition cursor-pointer"
                            >
                                <Plus className="w-4 h-4" />
                                <span>Apontar Produção / Perda</span>
                            </button>
                        )}
                    </div>
                </div>

                {/* Métricas Principais da OP */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                        <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Quantidade Planejada</span>
                        <div className="text-xl font-bold font-mono text-white mt-1">
                            {Number(order.planned_quantity).toFixed(2)} {order.item?.unit_of_measure?.code || 'UN'}
                        </div>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                        <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Produzido no Estoque</span>
                        <div className="text-xl font-bold font-mono text-emerald-400 mt-1">
                            {Number(order.produced_quantity).toFixed(2)} {order.item?.unit_of_measure?.code || 'UN'}
                        </div>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                        <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Refugo / Perda</span>
                        <div className="text-xl font-bold font-mono text-rose-400 mt-1">
                            {Number(order.scrapped_quantity).toFixed(2)} {order.item?.unit_of_measure?.code || 'UN'}
                        </div>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                        <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Almoxarifados</span>
                        <div className="text-xs text-slate-300 mt-1 space-y-0.5">
                            <div>Origem: <strong className="font-mono text-slate-100">[{order.materials_warehouse?.code}]</strong></div>
                            <div>Destino: <strong className="font-mono text-slate-100">[{order.warehouse?.code}]</strong></div>
                        </div>
                    </div>
                </div>

                {/* Insumos Empenhados */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-xl space-y-4">
                    <div className="flex items-center space-x-2 text-white font-bold text-base">
                        <Layers className="w-5 h-5 text-indigo-400" />
                        <span>Insumos Empenhados & Consumo de Matéria-Prima</span>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-300">
                            <thead className="bg-slate-950/70 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th className="py-2.5 px-3">Item / Insumo</th>
                                    <th className="py-2.5 px-3 text-right">Planejado</th>
                                    <th className="py-2.5 px-3 text-right">Consumido</th>
                                    <th className="py-2.5 px-3 text-right">Custo Unit. (CMP)</th>
                                    <th className="py-2.5 px-3 text-right">Total Acumulado</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {order.materials?.map((m) => (
                                    <tr key={m.id} className="hover:bg-slate-800/40">
                                        <td className="py-2.5 px-3">
                                            <div className="font-bold text-slate-100">{m.item?.name}</div>
                                            <div className="text-xs font-mono text-slate-400">{m.item?.sku}</div>
                                        </td>
                                        <td className="py-2.5 px-3 text-right font-mono text-slate-200">
                                            {Number(m.planned_quantity).toFixed(4)} {m.unit_of_measure?.code}
                                        </td>
                                        <td className="py-2.5 px-3 text-right font-mono text-emerald-400">
                                            {Number(m.consumed_quantity).toFixed(4)} {m.unit_of_measure?.code}
                                        </td>
                                        <td className="py-2.5 px-3 text-right font-mono text-slate-300">
                                            R$ {Number(m.unit_cost).toFixed(2)}
                                        </td>
                                        <td className="py-2.5 px-3 text-right font-mono font-bold text-slate-100">
                                            R$ {Number(m.total_cost).toFixed(2)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Roteiro e Operações da OP */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-xl space-y-4">
                    <div className="flex items-center space-x-2 text-white font-bold text-base">
                        <Clock className="w-5 h-5 text-emerald-400" />
                        <span>Roteiro de Operações no Chão de Fábrica</span>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {order.operations?.map((op) => (
                            <div key={op.id} className="bg-slate-950 border border-slate-800 p-4 rounded-xl flex items-center justify-between">
                                <div className="space-y-1">
                                    <div className="flex items-center space-x-2">
                                        <span className="font-mono text-xs bg-slate-800 px-2 py-0.5 rounded text-indigo-400 font-bold">
                                            Seq {op.sequence}
                                        </span>
                                        <span className="text-sm font-bold text-white">
                                            {op.work_center?.name}
                                        </span>
                                    </div>
                                    <div className="text-xs text-slate-400">
                                        Tempo acumulado: <strong className="font-mono text-slate-200">{formatSeconds(op.executed_time_seconds)}</strong>
                                    </div>
                                    {op.operator && (
                                        <div className="text-[11px] text-slate-400">
                                            Operador: <span className="text-slate-300 font-semibold">{op.operator.name}</span>
                                        </div>
                                    )}
                                </div>

                                <span className={`text-xs px-2.5 py-1 rounded-full font-bold uppercase ${
                                    op.status === 'COMPLETED' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' :
                                    op.status === 'IN_PROGRESS' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20 animate-pulse' :
                                    op.status === 'PAUSED' ? 'bg-orange-500/10 text-orange-400 border border-orange-500/20' :
                                    'bg-slate-800 text-slate-400'
                                }`}>
                                    {op.status}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Modal de Apontamento de Produção */}
            {appointmentModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full overflow-hidden shadow-2xl">
                        <div className="flex items-center justify-between p-5 border-b border-slate-800 bg-slate-900/60">
                            <div>
                                <h3 className="text-base font-bold text-white">Apontar Produção / Baixa de Kardex</h3>
                                <p className="text-xs text-slate-400">OP {order.order_number}</p>
                            </div>
                            <button
                                onClick={() => setAppointmentModalOpen(false)}
                                className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleAppointmentSubmit} className="p-5 space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Quantidade Boa Produzida <span className="text-rose-400">*</span>
                                </label>
                                <input
                                    type="number"
                                    step="0.0001"
                                    value={data.produced_quantity}
                                    onChange={(e) => setData('produced_quantity', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                    required
                                />
                                {errors.produced_quantity && <p className="text-xs text-rose-400 mt-1">{errors.produced_quantity}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Quantidade de Refugo / Perda (Scrap)
                                </label>
                                <input
                                    type="number"
                                    step="0.0001"
                                    value={data.scrapped_quantity}
                                    onChange={(e) => setData('scrapped_quantity', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Observações do Apontamento
                                </label>
                                <textarea
                                    rows={2}
                                    placeholder="Ex: Turno da manhã, lote de chapas sem defeitos..."
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div className="bg-indigo-950/40 border border-indigo-500/20 p-3 rounded-lg text-xs text-indigo-300 space-y-1">
                                <p className="font-semibold flex items-center space-x-1">
                                    <AlertCircle className="w-3.5 h-3.5" />
                                    <span>Integração Atômica Kardex:</span>
                                </p>
                                <p>
                                    As matérias-primas serão baixadas proporcionalmente no depósito de origem e a entrada do produto acabado será creditada no depósito de destino sob lock atômico.
                                </p>
                            </div>

                            <div className="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setAppointmentModalOpen(false)}
                                    className="px-4 py-2 rounded-lg text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold px-5 py-2 rounded-lg shadow-lg shadow-indigo-950/50 transition cursor-pointer"
                                >
                                    Confirmar Apontamento
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </TenantDesktopLayout>
    );
}
