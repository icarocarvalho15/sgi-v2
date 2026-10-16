import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import ProductionSubNav from '../Components/ProductionSubNav';
import { PaginatedResponse } from '@/types';
import { 
    Building2, 
    Plus, 
    CheckCircle2, 
    XCircle, 
    X, 
    Clock, 
    DollarSign,
    Layers
} from 'lucide-react';

interface WorkCenter {
    id: string;
    code: string;
    name: string;
    description: string | null;
    hourly_cost: string;
    capacity_per_day_hours: string;
    is_active: boolean;
}

interface Props {
    workCenters: PaginatedResponse<WorkCenter>;
    filters: {
        search: string;
    };
}

export default function WorkCentersIndex({ workCenters }: Props) {
    const [modalOpen, setModalOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        name: '',
        description: '',
        hourly_cost: '0.0000',
        capacity_per_day_hours: '8.00',
        is_active: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/app/production/work-centers', {
            onSuccess: () => {
                reset();
                setModalOpen(false);
            },
        });
    };

    return (
        <TenantDesktopLayout title="Centros de Trabalho">
            <Head title="Centros de Trabalho - SGI v2.0" />

            <div className="space-y-6">
                <ProductionSubNav />

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center space-x-2 text-indigo-400 text-xs font-semibold uppercase tracking-wider mb-1">
                            <Layers className="w-4 h-4" />
                            <span>Engenharia de Processos</span>
                        </div>
                        <h1 className="text-2xl font-black text-white">Centros de Trabalho</h1>
                        <p className="text-sm text-slate-400">
                            Células, máquinas, bancadas e postos operacionais onde ocorrem as etapas do roteiro produtivo
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
                        <span>Novo Centro de Trabalho</span>
                    </button>
                </div>

                {/* Grid de Cards de Centros de Trabalho */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {workCenters.data.map((wc) => (
                        <div
                            key={wc.id}
                            className="bg-slate-900 border border-slate-800 rounded-xl p-5 hover:border-slate-700 transition relative flex flex-col justify-between shadow-lg"
                        >
                            <div>
                                <div className="flex items-start justify-between">
                                    <div className="flex items-center space-x-2.5">
                                        <div className="p-2.5 bg-indigo-500/10 text-indigo-400 rounded-lg">
                                            <Building2 className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <span className="text-[10px] font-mono font-bold text-indigo-400 uppercase tracking-wider">
                                                [{wc.code}]
                                            </span>
                                            <h3 className="text-base font-bold text-white">{wc.name}</h3>
                                        </div>
                                    </div>

                                    {wc.is_active ? (
                                        <span className="text-emerald-400 font-semibold text-[11px] flex items-center space-x-1">
                                            <CheckCircle2 className="w-3.5 h-3.5" />
                                            <span>Ativo</span>
                                        </span>
                                    ) : (
                                        <span className="text-slate-500 font-semibold text-[11px] flex items-center space-x-1">
                                            <XCircle className="w-3.5 h-3.5" />
                                            <span>Inativo</span>
                                        </span>
                                    )}
                                </div>

                                {wc.description && (
                                    <p className="text-xs text-slate-400 mt-3 line-clamp-2">
                                        {wc.description}
                                    </p>
                                )}
                            </div>

                            <div className="mt-5 pt-4 border-t border-slate-800 grid grid-cols-2 gap-2 text-xs">
                                <div className="flex items-center space-x-1.5 text-slate-300">
                                    <DollarSign className="w-4 h-4 text-emerald-400 shrink-0" />
                                    <span>
                                        R$ {Number(wc.hourly_cost).toFixed(2)}/h
                                    </span>
                                </div>

                                <div className="flex items-center space-x-1.5 text-slate-300">
                                    <Clock className="w-4 h-4 text-indigo-400 shrink-0" />
                                    <span>{Number(wc.capacity_per_day_hours).toFixed(1)}h / dia</span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Modal de Criação */}
            {modalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full overflow-hidden shadow-2xl">
                        <div className="flex items-center justify-between p-5 border-b border-slate-800 bg-slate-900/60">
                            <div>
                                <h3 className="text-base font-bold text-white">Cadastrar Centro de Trabalho</h3>
                                <p className="text-xs text-slate-400">Máquina, setor ou bancada de manufatura</p>
                            </div>
                            <button
                                onClick={() => setModalOpen(false)}
                                className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="p-5 space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Código Sigla <span className="text-rose-400">*</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ex: CT-CORTE, CT-USINAGEM"
                                    value={data.code}
                                    onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                    required
                                />
                                {errors.code && <p className="text-xs text-rose-400 mt-1">{errors.code}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Nome do Centro de Trabalho <span className="text-rose-400">*</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ex: Torno CNC Mazak ou Bancada de Montagem"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    required
                                />
                                {errors.name && <p className="text-xs text-rose-400 mt-1">{errors.name}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Custo / Hora (R$) <span className="text-rose-400">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.hourly_cost}
                                        onChange={(e) => setData('hourly_cost', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                        required
                                    />
                                    {errors.hourly_cost && <p className="text-xs text-rose-400 mt-1">{errors.hourly_cost}</p>}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Capacidade (h/dia) <span className="text-rose-400">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        value={data.capacity_per_day_hours}
                                        onChange={(e) => setData('capacity_per_day_hours', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                        required
                                    />
                                    {errors.capacity_per_day_hours && <p className="text-xs text-rose-400 mt-1">{errors.capacity_per_day_hours}</p>}
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Descrição Operacional
                                </label>
                                <textarea
                                    rows={2}
                                    placeholder="Ex: Célula automatizada com operador dedicado..."
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div className="flex items-center space-x-2 pt-1">
                                <label className="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-0"
                                    />
                                    <span>Ativo para Alocação em Roteiros</span>
                                </label>
                            </div>

                            <div className="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
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
                                    className="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold px-5 py-2 rounded-lg shadow-lg shadow-indigo-950/50 transition cursor-pointer"
                                >
                                    Salvar Centro de Trabalho
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </TenantDesktopLayout>
    );
}
