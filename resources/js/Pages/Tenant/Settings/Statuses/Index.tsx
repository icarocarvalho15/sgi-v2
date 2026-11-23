import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { 
    Sliders, 
    Plus, 
    Edit2, 
    Trash2, 
    CheckCircle2, 
    Kanban, 
    FileText, 
    Layers,
    ArrowUpDown,
    Palette,
    Tag,
    AlertCircle
} from 'lucide-react';

interface TenantStatus {
    id: string;
    tenant_id: string;
    module: string;
    name: string;
    slug: string;
    color: string;
    position: number;
    is_default: boolean;
    is_final: boolean;
}

interface Props {
    statusesByModule: {
        crm_deals: TenantStatus[];
        quotes: TenantStatus[];
        production_orders: TenantStatus[];
    };
}

export default function StatusesIndex({ statusesByModule }: Props) {
    const [activeTab, setActiveTab] = useState<'quotes' | 'production_orders' | 'crm_deals'>('quotes');
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [editingStatus, setEditingStatus] = useState<TenantStatus | null>(null);

    // Form para criar novo status
    const createForm = useForm({
        module: activeTab,
        name: '',
        color: '#6366f1',
        is_default: false,
        is_final: false,
    });

    // Form para editar status
    const editForm = useForm({
        name: '',
        color: '#6366f1',
        position: 1,
        is_default: false,
        is_final: false,
    });

    const handleTabChange = (tab: 'quotes' | 'production_orders' | 'crm_deals') => {
        setActiveTab(tab);
        createForm.setData('module', tab);
    };

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.setData('module', activeTab);
        createForm.post('/settings/statuses', {
            onSuccess: () => {
                setIsCreateOpen(false);
                createForm.reset({
                    module: activeTab,
                    name: '',
                    color: '#6366f1',
                    is_default: false,
                    is_final: false,
                });
            },
        });
    };

    const openEditModal = (status: TenantStatus) => {
        setEditingStatus(status);
        editForm.setData({
            name: status.name,
            color: status.color,
            position: status.position,
            is_default: status.is_default,
            is_final: status.is_final,
        });
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingStatus) return;

        editForm.put(`/settings/statuses/${editingStatus.id}`, {
            onSuccess: () => {
                setEditingStatus(null);
            },
        });
    };

    const handleDelete = (status: TenantStatus) => {
        if (confirm(`Tem certeza que deseja remover o status "${status.name}"?`)) {
            router.delete(`/settings/statuses/${status.id}`);
        }
    };

    const currentStatuses = statusesByModule[activeTab] || [];

    const tabConfig = {
        quotes: {
            title: 'Orçamentos & Funil de Vendas',
            description: 'Fases do funil de vendas, cálculo técnico, envio e aprovação de propostas de brindes/impressos.',
            icon: FileText,
        },
        production_orders: {
            title: 'Chão de Fábrica & OPs (PCP)',
            description: 'Etapas de impressão, acabamento, montagem e despacho das ordens de produção.',
            icon: Layers,
        },
        crm_deals: {
            title: 'Oportunidades Comerciais (CRM)',
            description: 'Etapas de negociação do pipeline comercial de clientes e agências.',
            icon: Kanban,
        },
    };

    const currentMeta = tabConfig[activeTab];

    return (
        <AppLayout title="Status Customizados do Tenant">
            <Head title="Configurações - Status Dinâmicos do Tenant" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center gap-1">
                                <Palette className="w-3 h-3" /> Motor de Status Customizáveis
                            </span>
                        </div>
                        <h1 className="text-2xl font-bold text-white tracking-tight">Personalização de Status Operacionais</h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Configure as etapas de trabalho exclusivas da sua gráfica (CRM, Orçamentos e Chão de Fábrica) sem dependências hardcoded.
                        </p>
                    </div>

                    <button
                        onClick={() => {
                            createForm.setData('module', activeTab);
                            setIsCreateOpen(true);
                        }}
                        className="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl flex items-center gap-2 transition-colors shadow-lg shadow-indigo-600/20"
                    >
                        <Plus className="w-4 h-4" />
                        Adicionar Novo Status
                    </button>
                </div>

                {/* Abas por Módulo */}
                <div className="flex gap-2 border-b border-slate-800 pb-2 overflow-x-auto">
                    {(Object.keys(tabConfig) as Array<keyof typeof tabConfig>).map((key) => {
                        const tab = tabConfig[key];
                        const Icon = tab.icon;
                        const active = activeTab === key;
                        const count = statusesByModule[key]?.length || 0;

                        return (
                            <button
                                key={key}
                                onClick={() => handleTabChange(key)}
                                className={`px-4 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2.5 transition-all ${
                                    active
                                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                                        : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800'
                                }`}
                            >
                                <Icon className="w-4 h-4" />
                                <span>{tab.title}</span>
                                <span className={`px-2 py-0.5 rounded-full text-xs font-mono ${
                                    active ? 'bg-indigo-800 text-white' : 'bg-slate-800 text-slate-400'
                                }`}>
                                    {count}
                                </span>
                            </button>
                        );
                    })}
                </div>

                {/* Descrição do Módulo Ativo */}
                <div className="bg-slate-900/60 p-4 rounded-xl border border-slate-800 flex items-center justify-between">
                    <p className="text-sm text-slate-300">
                        {currentMeta.description}
                    </p>
                    <span className="text-xs text-slate-500 font-mono">
                        Módulo: {activeTab}
                    </span>
                </div>

                {/* Lista de Status */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {currentStatuses.map((st) => (
                        <div
                            key={st.id}
                            className="bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition-all flex flex-col justify-between space-y-4 shadow-lg"
                        >
                            <div className="flex items-start justify-between">
                                <div className="flex items-center gap-3">
                                    <div
                                        className="w-5 h-5 rounded-full shrink-0 shadow-sm border border-white/20"
                                        style={{ backgroundColor: st.color }}
                                    />
                                    <div>
                                        <h3 className="font-semibold text-white text-base leading-snug">{st.name}</h3>
                                        <span className="text-xs text-slate-500 font-mono">slug: {st.slug}</span>
                                    </div>
                                </div>

                                <div className="flex items-center gap-1.5">
                                    <button
                                        onClick={() => openEditModal(st)}
                                        className="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                                        title="Editar Status"
                                    >
                                        <Edit2 className="w-4 h-4" />
                                    </button>
                                    <button
                                        onClick={() => handleDelete(st)}
                                        className="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition-colors"
                                        title="Remover Status"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <div className="pt-3 border-t border-slate-800 flex items-center justify-between text-xs">
                                <span className="text-slate-400 flex items-center gap-1 font-mono">
                                    <ArrowUpDown className="w-3.5 h-3.5 text-slate-500" />
                                    Posição: {st.position}
                                </span>

                                <div className="flex items-center gap-1.5">
                                    {st.is_default && (
                                        <span className="px-2 py-0.5 rounded text-[10px] font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20">
                                            Inicial / Padrão
                                        </span>
                                    )}
                                    {st.is_final && (
                                        <span className="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            Conclusivo
                                        </span>
                                    )}
                                    {!st.is_default && !st.is_final && (
                                        <span className="px-2 py-0.5 rounded text-[10px] text-slate-500 bg-slate-800">
                                            Em Andamento
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {currentStatuses.length === 0 && (
                    <div className="bg-slate-900 border border-dashed border-slate-800 rounded-2xl p-12 text-center space-y-3">
                        <Tag className="w-8 h-8 text-slate-600 mx-auto" />
                        <h3 className="text-white font-medium">Nenhum status cadastrado para este módulo</h3>
                        <p className="text-slate-400 text-sm max-w-sm mx-auto">
                            Clique em "Adicionar Novo Status" para definir a primeira etapa deste fluxo.
                        </p>
                    </div>
                )}
            </div>

            {/* Modal: Criar Status */}
            {isCreateOpen && (
                <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                <Plus className="w-5 h-5 text-indigo-400" />
                                Novo Status ({tabConfig[activeTab].title})
                            </h3>
                            <button
                                onClick={() => setIsCreateOpen(false)}
                                className="text-slate-400 hover:text-white text-sm"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleCreate} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                    Nome da Etapa / Status
                                </label>
                                <input
                                    type="text"
                                    required
                                    placeholder="Ex: Em Prova de Cor / Aguardando Chapas"
                                    value={createForm.data.name}
                                    onChange={(e) => createForm.setData('name', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500"
                                />
                                {createForm.errors.name && (
                                    <span className="text-xs text-rose-400 mt-1 block">{createForm.errors.name}</span>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                    Cor Identificadora (#HEX)
                                </label>
                                <div className="flex items-center gap-3">
                                    <input
                                        type="color"
                                        value={createForm.data.color}
                                        onChange={(e) => createForm.setData('color', e.target.value)}
                                        className="w-11 h-11 rounded-xl bg-slate-950 border border-slate-700 cursor-pointer p-1"
                                    />
                                    <input
                                        type="text"
                                        required
                                        value={createForm.data.color}
                                        onChange={(e) => createForm.setData('color', e.target.value)}
                                        placeholder="#6366f1"
                                        className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                                {createForm.errors.color && (
                                    <span className="text-xs text-rose-400 mt-1 block">{createForm.errors.color}</span>
                                )}
                            </div>

                            <div className="space-y-2.5 pt-2 border-t border-slate-800">
                                <label className="flex items-center gap-2.5 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={createForm.data.is_default}
                                        onChange={(e) => createForm.setData('is_default', e.target.checked)}
                                        className="rounded border-slate-700 bg-slate-950 text-indigo-600 focus:ring-0"
                                    />
                                    <span className="text-sm text-slate-300">Definir como Status Inicial Padrão ao criar</span>
                                </label>

                                <label className="flex items-center gap-2.5 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={createForm.data.is_final}
                                        onChange={(e) => createForm.setData('is_final', e.target.checked)}
                                        className="rounded border-slate-700 bg-slate-950 text-indigo-600 focus:ring-0"
                                    />
                                    <span className="text-sm text-slate-300">Marcar como Etapa Conclusiva (Finalizada)</span>
                                </label>
                            </div>

                            <div className="pt-3 border-t border-slate-800 flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => setIsCreateOpen(false)}
                                    className="px-4 py-2 rounded-xl text-slate-400 hover:text-white text-sm"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={createForm.processing}
                                    className="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-sm transition-colors shadow-lg shadow-indigo-600/20"
                                >
                                    {createForm.processing ? 'Salvando...' : 'Criar Status'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal: Editar Status */}
            {editingStatus && (
                <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                <Edit2 className="w-5 h-5 text-indigo-400" />
                                Editar Status: {editingStatus.name}
                            </h3>
                            <button
                                onClick={() => setEditingStatus(null)}
                                className="text-slate-400 hover:text-white text-sm"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleUpdate} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                    Nome da Etapa
                                </label>
                                <input
                                    type="text"
                                    required
                                    value={editForm.data.name}
                                    onChange={(e) => editForm.setData('name', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                        Cor (#HEX)
                                    </label>
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="color"
                                            value={editForm.data.color}
                                            onChange={(e) => editForm.setData('color', e.target.value)}
                                            className="w-10 h-10 rounded-xl bg-slate-950 border border-slate-700 cursor-pointer p-1"
                                        />
                                        <input
                                            type="text"
                                            required
                                            value={editForm.data.color}
                                            onChange={(e) => editForm.setData('color', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-700 rounded-xl px-2.5 py-2 text-xs font-mono text-white focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                        Ordem (Posição)
                                    </label>
                                    <input
                                        type="number"
                                        min={1}
                                        value={editForm.data.position}
                                        onChange={(e) => editForm.setData('position', Number(e.target.value))}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm font-mono text-white focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2.5 pt-2 border-t border-slate-800">
                                <label className="flex items-center gap-2.5 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={editForm.data.is_default}
                                        onChange={(e) => editForm.setData('is_default', e.target.checked)}
                                        className="rounded border-slate-700 bg-slate-950 text-indigo-600 focus:ring-0"
                                    />
                                    <span className="text-sm text-slate-300">Status Inicial Padrão</span>
                                </label>

                                <label className="flex items-center gap-2.5 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={editForm.data.is_final}
                                        onChange={(e) => editForm.setData('is_final', e.target.checked)}
                                        className="rounded border-slate-700 bg-slate-950 text-indigo-600 focus:ring-0"
                                    />
                                    <span className="text-sm text-slate-300">Etapa Conclusiva (Finalizada)</span>
                                </label>
                            </div>

                            <div className="pt-3 border-t border-slate-800 flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => setEditingStatus(null)}
                                    className="px-4 py-2 rounded-xl text-slate-400 hover:text-white text-sm"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={editForm.processing}
                                    className="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-sm transition-colors shadow-lg shadow-indigo-600/20"
                                >
                                    {editForm.processing ? 'Salvando...' : 'Salvar Alterações'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
