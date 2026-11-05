import React, { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../../../Layouts/AppLayout';
import { ArrowLeft, Clock, Play, CheckCircle2, AlertTriangle, Layers, Cpu, Printer, User } from 'lucide-react';

interface WorkCenter {
    id: string;
    name: string;
    code: string;
}

interface Status {
    id: string;
    name: string;
    color: string;
}

interface Props {
    order: any;
    availableStatuses: Status[];
    workCenters: WorkCenter[];
}

export default function Show({ order, availableStatuses, workCenters }: Props) {
    const [changingStatus, setChangingStatus] = useState(false);

    const { data, setData, post, processing, reset } = useForm({
        work_center_id: workCenters[0]?.id || '',
        operation_name: 'Acerto / Tiragem de Máquina',
        started_at: new Date(Date.now() - 3600000).toISOString().slice(0, 16),
        ended_at: new Date().toISOString().slice(0, 16),
        good_quantity: 0,
        scrap_quantity: 0,
        notes: '',
    });

    const handleStatusChange = (statusId: string) => {
        setChangingStatus(true);
        router.post(
            `/production/orders/${order.id}/status`,
            { status_id: statusId },
            {
                onFinish: () => setChangingStatus(false),
            }
        );
    };

    const handleTimeLogSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/production/orders/${order.id}/log-time`, {
            onSuccess: () => reset('good_quantity', 'scrap_quantity', 'notes'),
        });
    };

    const progressPercent = Math.min(
        100,
        Math.round((order.produced_quantity / Math.max(1, order.target_quantity)) * 100)
    );

    return (
        <AppLayout title={`Chão de Fábrica — O.P. ${order.order_number}`}>
            <div className="space-y-6 max-w-6xl">
                {/* Header Actions */}
                <div className="flex items-center justify-between">
                    <Link
                        href="/production/orders"
                        className="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-slate-200 transition-colors"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Voltar para Ordens de Produção
                    </Link>

                    <div className="flex items-center gap-2">
                        <span className="text-xs text-slate-400 font-mono">
                            Status Atual:
                        </span>
                        <span
                            className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border"
                            style={{
                                color: order.status?.color,
                                borderColor: `${order.status?.color}40`,
                                backgroundColor: `${order.status?.color}15`,
                            }}
                        >
                            <span
                                className="w-2 h-2 rounded-full"
                                style={{ backgroundColor: order.status?.color }}
                            />
                            {order.status?.name}
                        </span>
                    </div>
                </div>

                {/* Stepper de Transição Rápida de Estágio no Chão de Fábrica */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 shadow-xl space-y-3">
                    <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">
                        Fluxo Produtivo Customizado da Gráfica (1-Toque para Avançar)
                    </span>

                    <div className="flex gap-2 overflow-x-auto pb-1">
                        {availableStatuses.map((s) => {
                            const isCurrent = s.id === order.status_id;
                            return (
                                <button
                                    key={s.id}
                                    type="button"
                                    disabled={changingStatus || isCurrent}
                                    onClick={() => handleStatusChange(s.id)}
                                    className={`shrink-0 flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all border ${
                                        isCurrent
                                            ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-600/30'
                                            : 'bg-slate-950/70 hover:bg-slate-800/80 text-slate-300 border-slate-800 hover:border-slate-700'
                                    }`}
                                >
                                    <span
                                        className="w-2 h-2 rounded-full"
                                        style={{ backgroundColor: s.color || '#6366F1' }}
                                    />
                                    {s.name}
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Dashboard da OP: Progresso & Ficha Técnica */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Painel de Produção & Apontamento */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Resumo da Tiragem */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-4">
                            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-800 pb-4">
                                <div>
                                    <span className="font-mono text-xs font-bold text-indigo-400">
                                        {order.order_number}
                                    </span>
                                    <h3 className="text-xl font-bold text-white mt-0.5">{order.product_name}</h3>
                                    {order.sales_order?.customer && (
                                        <span className="text-xs text-slate-400 block mt-1">
                                            Cliente: {order.sales_order.customer.name}
                                        </span>
                                    )}
                                </div>

                                <div className="text-right">
                                    <span className="text-xs text-slate-400 block">Progresso da Tiragem</span>
                                    <span className="text-2xl font-extrabold text-emerald-400 font-mono">
                                        {order.produced_quantity} / {order.target_quantity} un
                                    </span>
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <div className="flex justify-between text-xs text-slate-400 font-mono">
                                    <span>Conclusão da Produção</span>
                                    <span className="font-semibold text-slate-200">{progressPercent}%</span>
                                </div>
                                <div className="w-full bg-slate-950 rounded-full h-2.5 overflow-hidden border border-slate-800">
                                    <div
                                        className="bg-emerald-500 h-2.5 rounded-full transition-all duration-500 shadow-sm shadow-emerald-500/50"
                                        style={{ width: `${progressPercent}%` }}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Formulário de Apontamento de Chão de Fábrica */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-4">
                            <h4 className="text-base font-semibold text-slate-100 flex items-center gap-2 border-b border-slate-800 pb-3">
                                <Clock className="w-4 h-4 text-indigo-400" />
                                Registrar Apontamento de Turno / Operação
                            </h4>

                            <form onSubmit={handleTimeLogSubmit} className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-xs font-medium text-slate-400 mb-1">
                                            Máquina / Centro de Trabalho *
                                        </label>
                                        <select
                                            value={data.work_center_id}
                                            onChange={(e) => setData('work_center_id', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                        >
                                            {workCenters.map((wc) => (
                                                <option key={wc.id} value={wc.id}>
                                                    {wc.name} ({wc.code})
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-400 mb-1">
                                            Operação / Tarefa *
                                        </label>
                                        <input
                                            type="text"
                                            value={data.operation_name}
                                            onChange={(e) => setData('operation_name', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-400 mb-1">
                                            Quantidade Boa Produzida (un) *
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            value={data.good_quantity}
                                            onChange={(e) => setData('good_quantity', parseInt(e.target.value) || 0)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 font-mono"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-400 mb-1">
                                            Refugo / Perda de Acerto (un)
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            value={data.scrap_quantity}
                                            onChange={(e) => setData('scrap_quantity', parseInt(e.target.value) || 0)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 font-mono"
                                        />
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-emerald-600/20"
                                >
                                    {processing ? 'Gravando Apontamento...' : 'Confirmar Apontamento de Produção'}
                                </button>
                            </form>
                        </div>

                        {/* Histórico de Apontamentos da OP */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-3">
                            <h4 className="text-sm font-semibold text-slate-200 border-b border-slate-800 pb-2">
                                Histórico de Apontamentos de Chão de Fábrica
                            </h4>

                            <div className="divide-y divide-slate-800/60">
                                {order.time_logs?.length === 0 ? (
                                    <span className="text-xs text-slate-500 block py-3 text-center">
                                        Nenhum apontamento registrado ainda.
                                    </span>
                                ) : (
                                    order.time_logs?.map((log: any) => (
                                        <div key={log.id} className="py-2.5 flex items-center justify-between text-xs">
                                            <div>
                                                <span className="font-semibold text-slate-200 block">
                                                    {log.operation_name} — {log.work_center?.name}
                                                </span>
                                                <span className="text-slate-500 text-[11px]">
                                                    Início: {new Date(log.started_at).toLocaleString()}
                                                </span>
                                            </div>
                                            <div className="text-right font-mono">
                                                <span className="text-emerald-400 font-semibold block">
                                                    +{log.good_quantity} boas
                                                </span>
                                                {log.scrap_quantity > 0 && (
                                                    <span className="text-rose-400 text-[11px] block">
                                                        {log.scrap_quantity} refugo
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Especificações Técnicas e Materiais Empenhados */}
                    <div className="space-y-6">
                        {/* Especificações Industriais */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-3 text-xs">
                            <h4 className="text-sm font-semibold text-slate-200 flex items-center gap-2 border-b border-slate-800 pb-2">
                                <Cpu className="w-4 h-4 text-indigo-400" />
                                Especificações Técnicas
                            </h4>

                            <div className="space-y-2 text-slate-300">
                                {order.technical_notes?.colors_front !== undefined && (
                                    <div className="flex justify-between py-1 border-b border-slate-800/40">
                                        <span className="text-slate-400">Cores de Impressão:</span>
                                        <span className="font-mono font-semibold">
                                            {order.technical_notes.colors_front} x {order.technical_notes.colors_back}
                                        </span>
                                    </div>
                                )}
                                {order.technical_notes?.ctp_plates_count !== undefined && (
                                    <div className="flex justify-between py-1 border-b border-slate-800/40">
                                        <span className="text-slate-400">Chapas CTP:</span>
                                        <span className="font-mono font-semibold">
                                            {order.technical_notes.ctp_plates_count} un
                                        </span>
                                    </div>
                                )}
                                {order.technical_notes?.substrate_type && (
                                    <div className="flex justify-between py-1 border-b border-slate-800/40">
                                        <span className="text-slate-400">Substrato / Papel:</span>
                                        <span className="font-medium text-right max-w-[160px] truncate">
                                            {order.technical_notes.substrate_type}
                                        </span>
                                    </div>
                                )}
                                {order.technical_notes?.format_open && (
                                    <div className="flex justify-between py-1 border-b border-slate-800/40">
                                        <span className="text-slate-400">Formato Aberto:</span>
                                        <span className="font-mono">{order.technical_notes.format_open}</span>
                                    </div>
                                )}
                                {order.technical_notes?.customization_process && (
                                    <div className="flex justify-between py-1 border-b border-slate-800/40">
                                        <span className="text-slate-400">Personalização Brinde:</span>
                                        <span className="font-medium text-indigo-400">
                                            {order.technical_notes.customization_process}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Lista de Materiais da BOM Empenhados */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-3 text-xs">
                            <h4 className="text-sm font-semibold text-slate-200 flex items-center gap-2 border-b border-slate-800 pb-2">
                                <Layers className="w-4 h-4 text-indigo-400" />
                                Insumos Empenhados (BOM)
                            </h4>

                            <div className="space-y-2">
                                {order.materials?.length === 0 ? (
                                    <span className="text-slate-500 block text-center py-2">
                                        Nenhum insumo empenhado diretamente.
                                    </span>
                                ) : (
                                    order.materials?.map((mat: any) => (
                                        <div
                                            key={mat.id}
                                            className="p-2.5 bg-slate-950/60 border border-slate-800 rounded-lg flex justify-between items-center"
                                        >
                                            <div>
                                                <span className="font-medium text-slate-200 block">{mat.name}</span>
                                                <span className="text-[10px] text-slate-500 uppercase">
                                                    {mat.item_type}
                                                </span>
                                            </div>
                                            <span className="font-mono text-xs font-semibold text-indigo-400">
                                                {parseFloat(mat.planned_quantity).toFixed(2)} {mat.unit_of_measure}
                                            </span>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
