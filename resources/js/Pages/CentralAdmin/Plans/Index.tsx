import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import CentralAdminLayout from '@/Layouts/CentralAdminLayout';
import { Plan } from '@/types';
import { 
    CreditCard, 
    Plus, 
    Check, 
    X, 
    Users, 
    HardDrive, 
    Edit2,
    Shield
} from 'lucide-react';

interface Props {
    plans: Plan[];
}

export default function PlansIndex({ plans }: Props) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editingPlan, setEditingPlan] = useState<Plan | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        slug: '',
        description: '',
        price: '199.00',
        billing_interval: 'monthly',
        max_users: 5,
        max_storage_mb: 1024,
        is_active: true,
        features: {
            crm: true,
            inventory: true,
            engineering: false,
            production: false,
            finance: false,
            nfe_enabled: false,
            pwa_shopfloor: false,
        } as Record<string, boolean>,
    });

    const openCreateModal = () => {
        setEditingPlan(null);
        reset();
        setModalOpen(true);
    };

    const openEditModal = (plan: Plan) => {
        setEditingPlan(plan);
        setData({
            name: plan.name,
            slug: plan.slug,
            description: plan.description || '',
            price: String(plan.price),
            billing_interval: plan.billing_interval || 'monthly',
            max_users: plan.max_users || 5,
            max_storage_mb: plan.max_storage_mb || 1024,
            is_active: plan.is_active ?? true,
            features: {
                crm: Boolean(plan.features?.crm),
                inventory: Boolean(plan.features?.inventory),
                engineering: Boolean(plan.features?.engineering),
                production: Boolean(plan.features?.production),
                finance: Boolean(plan.features?.finance),
                nfe_enabled: Boolean(plan.features?.nfe_enabled),
                pwa_shopfloor: Boolean(plan.features?.pwa_shopfloor),
            },
        });
        setModalOpen(true);
    };

    const handleFeatureToggle = (featureKey: string) => {
        setData('features', {
            ...data.features,
            [featureKey]: !data.features[featureKey],
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingPlan) {
            put(`/admin/plans/${editingPlan.id}`, {
                onSuccess: () => {
                    setModalOpen(false);
                    reset();
                },
            });
        } else {
            post('/admin/plans', {
                onSuccess: () => {
                    setModalOpen(false);
                    reset();
                },
            });
        }
    };

    const formatCurrency = (val: string | number) => {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(val));
    };

    return (
        <CentralAdminLayout title="Planos & Preços">
            <Head title="Gestão de Planos - SGI v2.0" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-white tracking-tight">Planos SaaS & Precificação</h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Defina faixas de preço, limites de usuários, armazenamento e controle de features (Feature Gating).
                        </p>
                    </div>

                    <button
                        onClick={openCreateModal}
                        className="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-md shadow-indigo-600/20 flex items-center space-x-2 shrink-0"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Criar Novo Plano</span>
                    </button>
                </div>

                {/* Grid de Cards de Planos */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {plans.map((plan) => (
                        <div
                            key={plan.id}
                            className={`bg-slate-900 border rounded-xl p-6 flex flex-col justify-between transition shadow-sm ${
                                plan.slug === 'industrial-pro'
                                    ? 'border-indigo-500/50 shadow-indigo-500/10'
                                    : 'border-slate-800'
                            }`}
                        >
                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <h3 className="font-bold text-lg text-white">{plan.name}</h3>
                                    <button
                                        onClick={() => openEditModal(plan)}
                                        className="text-slate-400 hover:text-indigo-400 p-1 rounded hover:bg-slate-800 transition"
                                        title="Editar Plano"
                                    >
                                        <Edit2 className="w-4 h-4" />
                                    </button>
                                </div>

                                <p className="text-xs text-slate-400 min-h-10 leading-relaxed">
                                    {plan.description || 'Sem descrição cadastrada.'}
                                </p>

                                <div className="pt-2 border-t border-slate-800">
                                    <span className="text-3xl font-black text-white">{formatCurrency(plan.price)}</span>
                                    <span className="text-xs text-slate-400 font-medium"> / {plan.billing_interval === 'monthly' ? 'mês' : plan.billing_interval}</span>
                                </div>

                                <div className="space-y-2 pt-2 text-xs text-slate-300">
                                    <div className="flex items-center space-x-2">
                                        <Users className="w-4 h-4 text-indigo-400" />
                                        <span>Até <strong>{plan.max_users} usuários</strong> inclusos</span>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <HardDrive className="w-4 h-4 text-indigo-400" />
                                        <span>Armazenamento: <strong>{plan.max_storage_mb} MB</strong></span>
                                    </div>
                                </div>

                                <div className="pt-4 border-t border-slate-800 space-y-2">
                                    <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">
                                        Módulos Liberados:
                                    </p>

                                    {[
                                        { key: 'crm', label: 'CRM & Orçamentos' },
                                        { key: 'inventory', label: 'Suprimentos & Kardex' },
                                        { key: 'engineering', label: 'Engenharia (BOM)' },
                                        { key: 'production', label: 'Ordens de Produção' },
                                        { key: 'finance', label: 'Financeiro & Fluxo' },
                                        { key: 'nfe_enabled', label: 'Emissão Fiscal (NFe)' },
                                        { key: 'pwa_shopfloor', label: 'Chão de Fábrica Mobile' },
                                    ].map((f) => {
                                        const isEnabled = Boolean(plan.features?.[f.key]);
                                        return (
                                            <div key={f.key} className="flex items-center space-x-2 text-xs">
                                                {isEnabled ? (
                                                    <Check className="w-4 h-4 text-emerald-400 shrink-0" />
                                                ) : (
                                                    <X className="w-4 h-4 text-slate-400 shrink-0" />
                                                )}
                                                <span className={isEnabled ? 'text-slate-200' : 'text-slate-400'}>
                                                    {f.label}
                                                </span>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            <div className="pt-6 border-t border-slate-800 mt-6 flex items-center justify-between text-xs text-slate-400">
                                <span>Assinantes ativos:</span>
                                <span className="font-bold text-white bg-slate-800 px-2.5 py-1 rounded">
                                    {plan.active_subscribers_count || 0}
                                </span>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Modal de Criação / Edição de Plano */}
            {modalOpen && (
                <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-6 space-y-4 shadow-xl max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="font-bold text-lg text-white">
                                {editingPlan ? `Editar Plano: ${editingPlan.name}` : 'Criar Novo Plano'}
                            </h3>
                            <button onClick={() => setModalOpen(false)} className="text-slate-400 hover:text-white">✕</button>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4 text-xs">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label className="block font-semibold text-slate-300 mb-1">Nome do Plano *</label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                        placeholder="Ex: Industrial Pro"
                                        className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                                    />
                                </div>
                                <div>
                                    <label className="block font-semibold text-slate-300 mb-1">Slug</label>
                                    <input
                                        type="text"
                                        value={data.slug}
                                        onChange={(e) => setData('slug', e.target.value)}
                                        placeholder="industrial-pro"
                                        className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block font-semibold text-slate-300 mb-1">Descrição</label>
                                <textarea
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={2}
                                    placeholder="Descrição das funcionalidades para vendas..."
                                    className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                                />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label className="block font-semibold text-slate-300 mb-1">Preço (R$) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.price}
                                        onChange={(e) => setData('price', e.target.value)}
                                        required
                                        className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                                    />
                                </div>
                                <div>
                                    <label className="block font-semibold text-slate-300 mb-1">Limite Usuários *</label>
                                    <input
                                        type="number"
                                        value={data.max_users}
                                        onChange={(e) => setData('max_users', Number(e.target.value))}
                                        required
                                        className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                                    />
                                </div>
                                <div>
                                    <label className="block font-semibold text-slate-300 mb-1">Storage (MB) *</label>
                                    <input
                                        type="number"
                                        value={data.max_storage_mb}
                                        onChange={(e) => setData('max_storage_mb', Number(e.target.value))}
                                        required
                                        className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                                    />
                                </div>
                            </div>

                            <div className="border-t border-slate-800 pt-3">
                                <label className="block font-semibold text-slate-300 mb-2">Habilitação de Recursos (Feature Gating)</label>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    {[
                                        { key: 'crm', label: 'CRM & Orçamentos' },
                                        { key: 'inventory', label: 'Kardex & Suprimentos' },
                                        { key: 'engineering', label: 'Engenharia / BOM' },
                                        { key: 'production', label: 'Ordens de Produção' },
                                        { key: 'finance', label: 'Financeiro' },
                                        { key: 'nfe_enabled', label: 'Emissão Fiscal (NFe)' },
                                        { key: 'pwa_shopfloor', label: 'Chão de Fábrica Mobile' },
                                    ].map((f) => (
                                        <label key={f.key} className="flex items-center space-x-2 text-slate-300 p-2 bg-slate-950 rounded border border-slate-800 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={Boolean(data.features[f.key])}
                                                onChange={() => handleFeatureToggle(f.key)}
                                                className="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-0"
                                            />
                                            <span>{f.label}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <div className="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setModalOpen(false)}
                                    className="px-4 py-2 text-slate-400 hover:text-white"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-indigo-600 hover:bg-indigo-500 text-white font-bold px-4 py-2 rounded transition disabled:opacity-50"
                                >
                                    {processing ? 'Salvando...' : editingPlan ? 'Atualizar Plano' : 'Criar Plano'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </CentralAdminLayout>
    );
}
