import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import CommercialSubNav from '../Components/CommercialSubNav';
import { 
    Kanban, 
    Plus, 
    Building2, 
    Calendar, 
    DollarSign, 
    AlertCircle, 
    CheckCircle2, 
    X, 
    GripVertical,
    TrendingUp
} from 'lucide-react';

interface Partner {
    id: string;
    name: string;
    trade_name?: string;
    document_number: string;
}

interface Deal {
    id: string;
    pipeline_id: string;
    stage_id: string;
    partner_id: string;
    title: string;
    estimated_value: string;
    expected_close_date?: string;
    status: 'OPEN' | 'WON' | 'LOST';
    priority: 'LOW' | 'MEDIUM' | 'HIGH' | 'URGENT';
    partner?: Partner;
}

interface Stage {
    id: string;
    name: string;
    sequence: number;
    probability_percentage: string;
    color: string;
    is_won: boolean;
    is_lost: boolean;
    deals: Deal[];
}

interface Pipeline {
    id: string;
    name: string;
    stages: Stage[];
}

interface Props {
    pipeline: Pipeline;
    partners: Partner[];
}

export default function DealsIndex({ pipeline, partners }: Props) {
    const [stages, setStages] = useState<Stage[]>(pipeline?.stages || []);
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [draggedDealId, setDraggedDealId] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        pipeline_id: pipeline?.id || '',
        stage_id: pipeline?.stages?.[0]?.id || '',
        partner_id: partners?.[0]?.id || '',
        title: '',
        estimated_value: '',
        expected_close_date: '',
        priority: 'MEDIUM',
        notes: '',
    });

    const formatCurrency = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(isNaN(num) ? 0 : num);
    };

    const getPriorityBadge = (priority: string) => {
        switch (priority) {
            case 'URGENT':
                return <span className="px-2 py-0.5 text-[10px] font-bold rounded bg-rose-500/10 text-rose-400 border border-rose-500/20">Urgente</span>;
            case 'HIGH':
                return <span className="px-2 py-0.5 text-[10px] font-semibold rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">Alta</span>;
            case 'MEDIUM':
                return <span className="px-2 py-0.5 text-[10px] font-semibold rounded bg-blue-500/10 text-blue-400 border border-blue-500/20">Média</span>;
            default:
                return <span className="px-2 py-0.5 text-[10px] font-semibold rounded bg-slate-500/10 text-slate-400 border border-slate-500/20">Baixa</span>;
        }
    };

    // Drag and Drop Handlers
    const handleDragStart = (e: React.DragEvent, dealId: string) => {
        e.dataTransfer.setData('text/plain', dealId);
        setDraggedDealId(dealId);
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
    };

    const handleDrop = (e: React.DragEvent, targetStageId: string) => {
        e.preventDefault();
        const dealId = e.dataTransfer.getData('text/plain') || draggedDealId;
        if (!dealId) return;

        // Optimistic UI update
        let movedDeal: Deal | null = null;
        const newStages = stages.map((stage) => {
            const dealIndex = stage.deals.findIndex((d) => d.id === dealId);
            if (dealIndex > -1) {
                movedDeal = { ...stage.deals[dealIndex], stage_id: targetStageId };
                return {
                    ...stage,
                    deals: stage.deals.filter((d) => d.id !== dealId),
                };
            }
            return stage;
        });

        if (movedDeal) {
            const finalStages = newStages.map((stage) => {
                if (stage.id === targetStageId && movedDeal) {
                    return {
                        ...stage,
                        deals: [movedDeal, ...stage.deals],
                    };
                }
                return stage;
            });
            setStages(finalStages);

            // Persist to backend
            router.patch(`/app/crm/deals/${dealId}/stage`, {
                stage_id: targetStageId,
            }, {
                preserveState: true,
                onError: () => {
                    // Revert if error
                    setStages(pipeline.stages);
                },
            });
        }

        setDraggedDealId(null);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/app/crm/deals', {
            onSuccess: () => {
                setIsCreateOpen(false);
                reset();
                router.reload({ only: ['pipeline'] });
            },
        });
    };

    const totalPipelineValue = stages.reduce((acc, stage) => {
        return acc + stage.deals.reduce((sAcc, d) => sAcc + (parseFloat(d.estimated_value) || 0), 0);
    }, 0);

    return (
        <TenantDesktopLayout title="Funil de Vendas - CRM">
            <Head title="Funil de Vendas - CRM" />

            <div className="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <CommercialSubNav />

                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-xl font-bold text-white flex items-center gap-2">
                            <Kanban className="w-6 h-6 text-indigo-400" />
                            {pipeline?.name || 'Funil Comercial'}
                        </h1>
                        <p className="text-xs text-slate-400 mt-1 flex items-center gap-3">
                            <span>Arraste os cards entre as etapas para atualizar o status das oportunidades.</span>
                            <span className="font-semibold text-emerald-400">Total no Pipeline: {formatCurrency(totalPipelineValue)}</span>
                        </p>
                    </div>

                    <button
                        onClick={() => setIsCreateOpen(true)}
                        className="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm self-start md:self-auto"
                    >
                        <Plus className="w-4 h-4" />
                        Nova Oportunidade
                    </button>
                </div>

                {/* Kanban Board Horizontal */}
                <div className="flex gap-4 overflow-x-auto pb-6 items-start min-h-[calc(100vh-280px)]">
                    {stages.map((stage) => {
                        const stageTotal = stage.deals.reduce((acc, d) => acc + (parseFloat(d.estimated_value) || 0), 0);

                        return (
                            <div
                                key={stage.id}
                                onDragOver={handleDragOver}
                                onDrop={(e) => handleDrop(e, stage.id)}
                                className="w-80 shrink-0 bg-slate-900/90 border border-slate-800 rounded-xl flex flex-col max-h-[calc(100vh-250px)] shadow-md"
                            >
                                {/* Stage Header */}
                                <div className="p-3.5 border-b border-slate-800 flex items-center justify-between bg-slate-950/60 rounded-t-xl">
                                    <div className="flex items-center gap-2">
                                        <div 
                                            className="w-2.5 h-2.5 rounded-full" 
                                            style={{ backgroundColor: stage.color || '#6366f1' }}
                                        />
                                        <h3 className="text-xs font-bold text-white truncate">{stage.name}</h3>
                                        <span className="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-slate-800 text-slate-400">
                                            {stage.deals.length}
                                        </span>
                                    </div>
                                    <span className="text-[11px] font-semibold text-slate-300">
                                        {formatCurrency(stageTotal)}
                                    </span>
                                </div>

                                {/* Deals Cards List */}
                                <div className="p-3 overflow-y-auto space-y-3 flex-1 min-h-[120px]">
                                    {stage.deals.length === 0 ? (
                                        <div className="h-24 border border-dashed border-slate-800 rounded-lg flex items-center justify-center text-[11px] text-slate-600">
                                            Nenhum negócio aqui
                                        </div>
                                    ) : (
                                        stage.deals.map((deal) => (
                                            <div
                                                key={deal.id}
                                                draggable
                                                onDragStart={(e) => handleDragStart(e, deal.id)}
                                                className="bg-slate-950 border border-slate-800 hover:border-slate-700 p-3.5 rounded-xl cursor-grab active:cursor-grabbing transition shadow-sm hover:shadow-md group relative"
                                            >
                                                <div className="flex items-start justify-between gap-2 mb-2">
                                                    <h4 className="text-xs font-bold text-white group-hover:text-indigo-400 transition line-clamp-2">
                                                        {deal.title}
                                                    </h4>
                                                    <GripVertical className="w-3.5 h-3.5 text-slate-600 shrink-0 opacity-0 group-hover:opacity-100 transition" />
                                                </div>

                                                {deal.partner && (
                                                    <div className="flex items-center gap-1.5 text-[11px] text-slate-400 mb-2.5 truncate">
                                                        <Building2 className="w-3 h-3 text-slate-500 shrink-0" />
                                                        <span className="truncate">{deal.partner.name}</span>
                                                    </div>
                                                )}

                                                <div className="flex items-center justify-between pt-2 border-t border-slate-900 text-xs">
                                                    <span className="font-bold text-emerald-400">
                                                        {formatCurrency(deal.estimated_value)}
                                                    </span>
                                                    <div>
                                                        {getPriorityBadge(deal.priority)}
                                                    </div>
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Modal Nova Oportunidade */}
            {isCreateOpen && (
                <div className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl">
                        <div className="p-6 border-b border-slate-800 flex items-center justify-between">
                            <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                <TrendingUp className="w-5 h-5 text-indigo-400" />
                                Nova Oportunidade Comercial
                            </h3>
                            <button
                                onClick={() => setIsCreateOpen(false)}
                                className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Título do Negócio *</label>
                                <input
                                    type="text"
                                    placeholder="Ex: Fornecimento de 500 Unidades de Válvulas..."
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    required
                                />
                                {errors.title && <p className="text-xs text-rose-400 mt-1">{errors.title}</p>}
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Cliente / Parceiro *</label>
                                    <select
                                        value={data.partner_id}
                                        onChange={(e) => setData('partner_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                        required
                                    >
                                        <option value="">Selecione um cliente...</option>
                                        {partners.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                {p.name} ({p.document_number})
                                            </option>
                                        ))}
                                    </select>
                                    {errors.partner_id && <p className="text-xs text-rose-400 mt-1">{errors.partner_id}</p>}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Etapa Inicial *</label>
                                    <select
                                        value={data.stage_id}
                                        onChange={(e) => setData('stage_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                        required
                                    >
                                        {stages.map((s) => (
                                            <option key={s.id} value={s.id}>
                                                {s.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Valor Estimado (R$)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        placeholder="0,00"
                                        value={data.estimated_value}
                                        onChange={(e) => setData('estimated_value', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Prioridade</label>
                                    <select
                                        value={data.priority}
                                        onChange={(e) => setData('priority', e.target.value as any)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="LOW">Baixa</option>
                                        <option value="MEDIUM">Média</option>
                                        <option value="HIGH">Alta</option>
                                        <option value="URGENT">Urgente</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Previsão de Fechamento</label>
                                <input
                                    type="date"
                                    value={data.expected_close_date}
                                    onChange={(e) => setData('expected_close_date', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setIsCreateOpen(false)}
                                    className="px-4 py-2 rounded-lg text-sm font-medium text-slate-400 hover:text-white transition"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2 rounded-lg text-sm font-semibold transition disabled:opacity-50"
                                >
                                    {processing ? 'Salvando...' : 'Criar Oportunidade'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </TenantDesktopLayout>
    );
}
