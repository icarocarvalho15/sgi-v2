import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import ProductionSubNav from '../Components/ProductionSubNav';
import { PaginatedResponse, Item, UnitOfMeasure } from '@/types';
import { 
    Cpu, 
    Plus, 
    CheckCircle2, 
    Calculator, 
    Layers, 
    X, 
    Trash2, 
    AlertCircle,
    ArrowRight
} from 'lucide-react';

interface WorkCenter {
    id: string;
    code: string;
    name: string;
}

interface BomItem {
    id: string;
    item: Item;
    unit_of_measure: UnitOfMeasure;
    quantity: string;
    scrap_rate_percentage: string;
    unit_cost: string;
    total_cost: string;
}

interface RoutingStep {
    id: string;
    work_center: WorkCenter;
    sequence: number;
    name: string;
    setup_time_minutes: string;
    run_time_minutes_per_unit: string;
}

interface Bom {
    id: string;
    code: string;
    version: number;
    description: string | null;
    batch_size: string;
    total_estimated_cost: string;
    is_active: boolean;
    is_default: boolean;
    item: Item;
    items: BomItem[];
    routing_steps: RoutingStep[];
}

interface Props {
    boms: PaginatedResponse<Bom>;
    manufacturedItems: Item[];
    allItems: Item[];
    units: UnitOfMeasure[];
    workCenters: WorkCenter[];
    filters: {
        search: string;
    };
}

export default function BomsIndex({
    boms,
    manufacturedItems,
    allItems,
    units,
    workCenters,
    filters,
}: Props) {
    const [modalOpen, setModalOpen] = useState(false);
    const [recalculatingId, setRecalculatingId] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        item_id: manufacturedItems[0]?.id || '',
        code: '',
        version: 1,
        description: '',
        batch_size: '1.0000',
        is_active: true,
        is_default: true,
        items: [
            {
                item_id: allItems[0]?.id || '',
                unit_of_measure_id: units[0]?.id || '',
                quantity: '1.0000',
                scrap_rate_percentage: '0.00',
                notes: '',
            },
        ],
        routing_steps: [
            {
                work_center_id: workCenters[0]?.id || '',
                sequence: 10,
                name: 'Corte e Preparação',
                setup_time_minutes: '15.00',
                run_time_minutes_per_unit: '5.00',
            },
        ],
    });

    const handleAddItemRow = () => {
        setData('items', [
            ...data.items,
            {
                item_id: allItems[0]?.id || '',
                unit_of_measure_id: units[0]?.id || '',
                quantity: '1.0000',
                scrap_rate_percentage: '0.00',
                notes: '',
            },
        ]);
    };

    const handleRemoveItemRow = (index: number) => {
        setData('items', data.items.filter((_, i) => i !== index));
    };

    const handleAddStepRow = () => {
        const nextSeq = (data.routing_steps.length + 1) * 10;
        setData('routing_steps', [
            ...data.routing_steps,
            {
                work_center_id: workCenters[0]?.id || '',
                sequence: nextSeq,
                name: 'Operação ' + nextSeq,
                setup_time_minutes: '10.00',
                run_time_minutes_per_unit: '5.00',
            },
        ]);
    };

    const handleRemoveStepRow = (index: number) => {
        setData('routing_steps', data.routing_steps.filter((_, i) => i !== index));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/app/production/boms', {
            onSuccess: () => {
                reset();
                setModalOpen(false);
            },
        });
    };

    const handleRecalculateRollup = (bomId: string) => {
        setRecalculatingId(bomId);
        router.post(`/app/production/boms/${bomId}/rollup`, {}, {
            onFinish: () => setRecalculatingId(null),
        });
    };

    return (
        <TenantDesktopLayout title="Fichas Técnicas (BOM)">
            <Head title="Fichas Técnicas (BOM) - SGI v2.0" />

            <div className="space-y-6">
                <ProductionSubNav />

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center space-x-2 text-indigo-400 text-xs font-semibold uppercase tracking-wider mb-1">
                            <Cpu className="w-4 h-4" />
                            <span>Engenharia de Produto</span>
                        </div>
                        <h1 className="text-2xl font-black text-white">Fichas Técnicas (Bill of Materials)</h1>
                        <p className="text-sm text-slate-400">
                            Estruturas versionadas de insumos, matérias-primas e roteiros produtivos com cálculo de custo estimado (Rollup)
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
                        <span>Nova Ficha Técnica</span>
                    </button>
                </div>

                {/* Lista de BOMs */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-300">
                            <thead className="bg-slate-950/70 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th className="py-3 px-4">Código / Versão</th>
                                    <th className="py-3 px-4">Produto Fabricado</th>
                                    <th className="py-3 px-4 text-right">Tamanho do Lote</th>
                                    <th className="py-3 px-4 text-center">Componentes</th>
                                    <th className="py-3 px-4 text-center">Etapas do Roteiro</th>
                                    <th className="py-3 px-4 text-right">Custo Estimado (Rollup)</th>
                                    <th className="py-3 px-4 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {boms.data.map((b) => (
                                    <tr key={b.id} className="hover:bg-slate-800/40 transition">
                                        <td className="py-3 px-4">
                                            <div className="flex items-center space-x-2">
                                                <span className="font-mono font-bold text-white">{b.code}</span>
                                                <span className="text-[10px] bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded font-mono">
                                                    v{b.version}
                                                </span>
                                                {b.is_default && (
                                                    <span className="text-[9px] bg-amber-500/10 text-amber-400 border border-amber-500/20 px-1.5 py-0.2 rounded font-semibold">
                                                        PADRÃO
                                                    </span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="py-3 px-4">
                                            <div>
                                                <div className="font-bold text-slate-100">{b.item?.name}</div>
                                                <div className="text-xs font-mono text-slate-400">{b.item?.sku}</div>
                                            </div>
                                        </td>
                                        <td className="py-3 px-4 text-right font-mono text-slate-200">
                                            {Number(b.batch_size).toFixed(2)} {b.item?.unit_of_measure?.code || 'UN'}
                                        </td>
                                        <td className="py-3 px-4 text-center">
                                            <span className="bg-slate-800/80 text-indigo-400 px-2 py-0.5 rounded-full text-xs font-semibold">
                                                {b.items?.length || 0} insumos
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-center">
                                            <span className="bg-slate-800/80 text-emerald-400 px-2 py-0.5 rounded-full text-xs font-semibold">
                                                {b.routing_steps?.length || 0} etapas
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right font-mono font-bold text-emerald-400">
                                            R$ {Number(b.total_estimated_cost).toFixed(2)}
                                        </td>
                                        <td className="py-3 px-4 text-center">
                                            <button
                                                onClick={() => handleRecalculateRollup(b.id)}
                                                disabled={recalculatingId === b.id}
                                                title="Recalcular Rollup de Custo da BOM"
                                                className="inline-flex items-center space-x-1.5 text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-indigo-300 px-2.5 py-1.5 rounded-lg border border-slate-700 transition"
                                            >
                                                <Calculator className={`w-3.5 h-3.5 ${recalculatingId === b.id ? 'animate-spin' : ''}`} />
                                                <span>Recalcular</span>
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Modal de Criação de BOM */}
            {modalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in overflow-y-auto">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-4xl w-full my-8 overflow-hidden shadow-2xl">
                        <div className="flex items-center justify-between p-5 border-b border-slate-800 bg-slate-900/60">
                            <div>
                                <h3 className="text-base font-bold text-white">Criar Ficha Técnica (BOM)</h3>
                                <p className="text-xs text-slate-400">Definição de materiais e roteiro para manufatura sob encomenda</p>
                            </div>
                            <button
                                onClick={() => setModalOpen(false)}
                                className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Dados Básicos */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="md:col-span-2">
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Produto Acabado a Fabricar <span className="text-rose-400">*</span>
                                    </label>
                                    <select
                                        value={data.item_id}
                                        onChange={(e) => setData('item_id', e.target.value)}
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
                                        Código da BOM <span className="text-rose-400">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Ex: BOM-MAQ-01"
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                        required
                                    />
                                    {errors.code && <p className="text-xs text-rose-400 mt-1">{errors.code}</p>}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Tamanho do Lote Base <span className="text-rose-400">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        step="0.0001"
                                        value={data.batch_size}
                                        onChange={(e) => setData('batch_size', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                        required
                                    />
                                </div>

                                <div className="md:col-span-2">
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Descrição / Observações de Engenharia
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Ex: Versão de produção revisada com pintura eletrostática..."
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            {/* Insumos / Componentes da BOM */}
                            <div className="border-t border-slate-800 pt-5 space-y-3">
                                <div className="flex items-center justify-between">
                                    <h4 className="text-sm font-bold text-white flex items-center space-x-2">
                                        <Layers className="w-4 h-4 text-indigo-400" />
                                        <span>Lista de Materiais & Insumos</span>
                                    </h4>
                                    <button
                                        type="button"
                                        onClick={handleAddItemRow}
                                        className="text-xs bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-400 px-3 py-1.5 rounded-lg border border-indigo-500/30 transition flex items-center space-x-1"
                                    >
                                        <Plus className="w-3.5 h-3.5" />
                                        <span>Adicionar Insumo</span>
                                    </button>
                                </div>

                                <div className="space-y-2">
                                    {data.items.map((row, idx) => (
                                        <div key={idx} className="flex items-center space-x-2 bg-slate-950 p-2.5 rounded-lg border border-slate-800">
                                            <div className="flex-1">
                                                <select
                                                    value={row.item_id}
                                                    onChange={(e) => {
                                                        const newItems = [...data.items];
                                                        newItems[idx].item_id = e.target.value;
                                                        setData('items', newItems);
                                                    }}
                                                    className="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1.5 text-xs text-white"
                                                >
                                                    {allItems.map((item) => (
                                                        <option key={item.id} value={item.id}>
                                                            [{item.sku}] {item.name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>

                                            <div className="w-28">
                                                <input
                                                    type="number"
                                                    step="0.0001"
                                                    placeholder="Qtd"
                                                    value={row.quantity}
                                                    onChange={(e) => {
                                                        const newItems = [...data.items];
                                                        newItems[idx].quantity = e.target.value;
                                                        setData('items', newItems);
                                                    }}
                                                    className="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1.5 text-xs text-white font-mono"
                                                />
                                            </div>

                                            <div className="w-24">
                                                <select
                                                    value={row.unit_of_measure_id}
                                                    onChange={(e) => {
                                                        const newItems = [...data.items];
                                                        newItems[idx].unit_of_measure_id = e.target.value;
                                                        setData('items', newItems);
                                                    }}
                                                    className="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1.5 text-xs text-white"
                                                >
                                                    {units.map((u) => (
                                                        <option key={u.id} value={u.id}>
                                                            {u.code}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>

                                            <div className="w-24">
                                                <input
                                                    type="number"
                                                    step="0.1"
                                                    placeholder="% Perda"
                                                    value={row.scrap_rate_percentage}
                                                    onChange={(e) => {
                                                        const newItems = [...data.items];
                                                        newItems[idx].scrap_rate_percentage = e.target.value;
                                                        setData('items', newItems);
                                                    }}
                                                    className="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1.5 text-xs text-white font-mono"
                                                />
                                            </div>

                                            {data.items.length > 1 && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleRemoveItemRow(idx)}
                                                    className="text-rose-400 hover:text-rose-300 p-1"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Roteiro Produtivo */}
                            <div className="border-t border-slate-800 pt-5 space-y-3">
                                <div className="flex items-center justify-between">
                                    <h4 className="text-sm font-bold text-white flex items-center space-x-2">
                                        <Cpu className="w-4 h-4 text-emerald-400" />
                                        <span>Etapas do Roteiro Produtivo</span>
                                    </h4>
                                    <button
                                        type="button"
                                        onClick={handleAddStepRow}
                                        className="text-xs bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 px-3 py-1.5 rounded-lg border border-emerald-500/30 transition flex items-center space-x-1"
                                    >
                                        <Plus className="w-3.5 h-3.5" />
                                        <span>Adicionar Etapa</span>
                                    </button>
                                </div>

                                <div className="space-y-2">
                                    {data.routing_steps.map((step, idx) => (
                                        <div key={idx} className="flex items-center space-x-2 bg-slate-950 p-2.5 rounded-lg border border-slate-800">
                                            <div className="w-16">
                                                <input
                                                    type="number"
                                                    placeholder="Seq"
                                                    value={step.sequence}
                                                    onChange={(e) => {
                                                        const newSteps = [...data.routing_steps];
                                                        newSteps[idx].sequence = Number(e.target.value);
                                                        setData('routing_steps', newSteps);
                                                    }}
                                                    className="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1.5 text-xs text-white font-mono"
                                                />
                                            </div>

                                            <div className="flex-1">
                                                <input
                                                    type="text"
                                                    placeholder="Nome da Operação (ex: Corte CNC)"
                                                    value={step.name}
                                                    onChange={(e) => {
                                                        const newSteps = [...data.routing_steps];
                                                        newSteps[idx].name = e.target.value;
                                                        setData('routing_steps', newSteps);
                                                    }}
                                                    className="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1.5 text-xs text-white"
                                                />
                                            </div>

                                            <div className="w-48">
                                                <select
                                                    value={step.work_center_id}
                                                    onChange={(e) => {
                                                        const newSteps = [...data.routing_steps];
                                                        newSteps[idx].work_center_id = e.target.value;
                                                        setData('routing_steps', newSteps);
                                                    }}
                                                    className="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1.5 text-xs text-white"
                                                >
                                                    {workCenters.map((wc) => (
                                                        <option key={wc.id} value={wc.id}>
                                                            [{wc.code}] {wc.name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>

                                            {data.routing_steps.length > 1 && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleRemoveStepRow(idx)}
                                                    className="text-rose-400 hover:text-rose-300 p-1"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="flex items-center justify-end space-x-3 pt-5 border-t border-slate-800">
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
                                    className="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold px-5 py-2.5 rounded-lg shadow-lg shadow-indigo-950/50 transition cursor-pointer"
                                >
                                    Salvar e Calcular Rollup
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </TenantDesktopLayout>
    );
}
