import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import LandlordLayout from '@/Layouts/LandlordLayout';
import { 
    CreditCard, 
    Plus, 
    Edit2, 
    Trash2, 
    Check, 
    Users, 
    Layers, 
    Calendar,
    Sparkles,
    ShieldCheck
} from 'lucide-react';

interface Plan {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    price: string | number;
    billing_interval: 'monthly' | 'yearly';
    max_users: number;
    max_monthly_orders: number;
    features: string[] | null;
    is_active: boolean;
    tenants_count?: number;
}

interface Props {
    plans: Plan[];
}

export default function PlansIndex({ plans }: Props) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [editingPlan, setEditingPlan] = useState<Plan | null>(null);
    const [featureInput, setFeatureInput] = useState('');

    const createForm = useForm({
        name: '',
        price: '',
        billing_interval: 'monthly',
        max_users: 5,
        max_monthly_orders: 200,
        description: '',
        features: [] as string[],
        is_active: true,
    });

    const editForm = useForm({
        name: '',
        price: '',
        billing_interval: 'monthly',
        max_users: 5,
        max_monthly_orders: 200,
        description: '',
        features: [] as string[],
        is_active: true,
    });

    const addFeatureToCreate = () => {
        if (!featureInput.trim()) return;
        createForm.setData('features', [...createForm.data.features, featureInput.trim()]);
        setFeatureInput('');
    };

    const removeFeatureFromCreate = (idx: number) => {
        createForm.setData('features', createForm.data.features.filter((_, i) => i !== idx));
    };

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/admin/plans', {
            onSuccess: () => {
                setIsCreateOpen(false);
                createForm.reset();
            },
        });
    };

    const openEditModal = (plan: Plan) => {
        setEditingPlan(plan);
        editForm.setData({
            name: plan.name,
            price: String(plan.price),
            billing_interval: plan.billing_interval,
            max_users: plan.max_users,
            max_monthly_orders: plan.max_monthly_orders,
            description: plan.description || '',
            features: plan.features || [],
            is_active: plan.is_active,
        });
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingPlan) return;

        editForm.put(`/admin/plans/${editingPlan.id}`, {
            onSuccess: () => {
                setEditingPlan(null);
            },
        });
    };

    const handleDelete = (plan: Plan) => {
        if (confirm(`Tem certeza que deseja desativar/excluir o plano "${plan.name}"?`)) {
            router.delete(`/admin/plans/${plan.id}`);
        }
    };

    return (
        <LandlordLayout title="Gestão de Planos SaaS">
            <Head title="Landlord - Planos & Assinaturas" />

            <div className="space-y-8">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center gap-1">
                                <CreditCard className="w-3.5 h-3.5" /> Monetização & Tiers de Assinatura
                            </span>
                        </div>
                        <h1 className="text-2xl font-bold text-white tracking-tight">Planos do SaaS</h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Defina os pacotes comerciais disponíveis para gráficas, birôs de CTP e indústrias de brindes.
                        </p>
                    </div>

                    <button
                        onClick={() => setIsCreateOpen(true)}
                        className="px-4 py-2.5 bg-amber-600 hover:bg-amber-500 text-slate-950 text-sm font-bold rounded-xl flex items-center gap-2 transition-colors shadow-lg shadow-amber-600/20"
                    >
                        <Plus className="w-4 h-4" />
                        Criar Novo Plano
                    </button>
                </div>

                {/* Grid de Planos */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {plans.map((p) => (
                        <div
                            key={p.id}
                            className="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between hover:border-slate-700 transition-all shadow-xl relative overflow-hidden"
                        >
                            {p.slug.includes('enterprise') && (
                                <div className="absolute top-0 right-0 bg-amber-500 text-slate-950 font-bold text-[10px] uppercase px-3 py-1 rounded-bl-xl tracking-wider">
                                    Enterprise
                                </div>
                            )}

                            <div className="space-y-4">
                                <div>
                                    <h3 className="text-xl font-bold text-white">{p.name}</h3>
                                    <p className="text-xs text-slate-400 mt-1 line-clamp-2 min-h-[32px]">
                                        {p.description || 'Sem descrição cadastrada.'}
                                    </p>
                                </div>

                                <div className="pt-3 border-t border-slate-800">
                                    <div className="flex items-baseline gap-1">
                                        <span className="text-3xl font-extrabold text-white">
                                            R$ {Number(p.price).toFixed(2)}
                                        </span>
                                        <span className="text-xs text-slate-400 font-medium">/mês</span>
                                    </div>
                                    <span className="text-xs text-emerald-400 font-semibold block mt-1">
                                        {p.tenants_count || 0} empresas ativas neste plano
                                    </span>
                                </div>

                                {/* Limites */}
                                <div className="bg-slate-950/60 p-3.5 rounded-xl border border-slate-800 space-y-2 text-xs text-slate-300">
                                    <div className="flex items-center justify-between">
                                        <span className="flex items-center gap-1.5 text-slate-400">
                                            <Users className="w-3.5 h-3.5 text-slate-500" />
                                            Limite de Usuários:
                                        </span>
                                        <span className="font-bold text-white">{p.max_users} contas</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="flex items-center gap-1.5 text-slate-400">
                                            <Layers className="w-3.5 h-3.5 text-slate-500" />
                                            Volume de OPs/mês:
                                        </span>
                                        <span className="font-bold text-white">{p.max_monthly_orders} ordens</span>
                                    </div>
                                </div>

                                {/* Funcionalidades */}
                                <div className="space-y-2 pt-2">
                                    <span className="text-[11px] uppercase font-bold text-slate-500 tracking-wider block">
                                        Módulos Inclusos:
                                    </span>
                                    <ul className="space-y-1.5 text-xs text-slate-300">
                                        {(p.features || []).map((feat, i) => (
                                            <li key={i} className="flex items-start gap-2">
                                                <Check className="w-3.5 h-3.5 text-emerald-400 shrink-0 mt-0.5" />
                                                <span>{feat}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>

                            <div className="pt-5 border-t border-slate-800 flex items-center justify-between mt-6">
                                <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                                    p.is_active
                                        ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                                        : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'
                                }`}>
                                    {p.is_active ? 'Ativo para Venda' : 'Inativo'}
                                </span>

                                <div className="flex items-center gap-2">
                                    <button
                                        onClick={() => openEditModal(p)}
                                        className="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                                        title="Editar Plano"
                                    >
                                        <Edit2 className="w-4 h-4" />
                                    </button>
                                    <button
                                        onClick={() => handleDelete(p)}
                                        className="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition-colors"
                                        title="Excluir Plano"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Modal: Criar Plano */}
            {isCreateOpen && (
                <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                <Plus className="w-5 h-5 text-amber-400" />
                                Criar Novo Plano SaaS
                            </h3>
                            <button
                                onClick={() => setIsCreateOpen(false)}
                                className="text-slate-400 hover:text-white text-sm"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleCreate} className="space-y-4">
                            <div className="grid grid-cols-2 gap-3">
                                <div className="col-span-2">
                                    <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                        Nome do Plano
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        placeholder="Ex: Gráfica Offset & Digital Pro"
                                        value={createForm.data.name}
                                        onChange={(e) => createForm.setData('name', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                        Preço Mensal (R$)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        placeholder="299.00"
                                        value={createForm.data.price}
                                        onChange={(e) => createForm.setData('price', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:outline-none focus:border-amber-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                        Intervalo de Cobrança
                                    </label>
                                    <select
                                        value={createForm.data.billing_interval}
                                        onChange={(e) => createForm.setData('billing_interval', e.target.value as any)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500"
                                    >
                                        <option value="monthly">Mensal</option>
                                        <option value="yearly">Anual</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                        Limite de Usuários
                                    </label>
                                    <input
                                        type="number"
                                        min={1}
                                        required
                                        value={createForm.data.max_users}
                                        onChange={(e) => createForm.setData('max_users', Number(e.target.value))}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:outline-none focus:border-amber-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                        Limite de OPs / mês
                                    </label>
                                    <input
                                        type="number"
                                        min={1}
                                        required
                                        value={createForm.data.max_monthly_orders}
                                        onChange={(e) => createForm.setData('max_monthly_orders', Number(e.target.value))}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:outline-none focus:border-amber-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                    Descrição Breve
                                </label>
                                <textarea
                                    rows={2}
                                    placeholder="Resumo dos benefícios e público-alvo deste plano..."
                                    value={createForm.data.description}
                                    onChange={(e) => createForm.setData('description', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-amber-500"
                                />
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
                                    className="px-5 py-2 bg-amber-600 hover:bg-amber-500 text-slate-950 font-bold rounded-xl text-sm transition-colors shadow-lg shadow-amber-600/20"
                                >
                                    {createForm.processing ? 'Salvando...' : 'Criar Plano'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal: Editar Plano */}
            {editingPlan && (
                <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                <Edit2 className="w-5 h-5 text-amber-400" />
                                Editar Plano: {editingPlan.name}
                            </h3>
                            <button
                                onClick={() => setEditingPlan(null)}
                                className="text-slate-400 hover:text-white text-sm"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleUpdate} className="space-y-4">
                            <div className="grid grid-cols-2 gap-3">
                                <div className="col-span-2">
                                    <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                        Nome do Plano
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={editForm.data.name}
                                        onChange={(e) => editForm.setData('name', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                        Preço Mensal (R$)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={editForm.data.price}
                                        onChange={(e) => editForm.setData('price', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:outline-none focus:border-amber-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                        Limite de Usuários
                                    </label>
                                    <input
                                        type="number"
                                        min={1}
                                        required
                                        value={editForm.data.max_users}
                                        onChange={(e) => editForm.setData('max_users', Number(e.target.value))}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:outline-none focus:border-amber-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                        Limite de OPs / mês
                                    </label>
                                    <input
                                        type="number"
                                        min={1}
                                        required
                                        value={editForm.data.max_monthly_orders}
                                        onChange={(e) => editForm.setData('max_monthly_orders', Number(e.target.value))}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:outline-none focus:border-amber-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                        Status de Venda
                                    </label>
                                    <select
                                        value={editForm.data.is_active ? 'true' : 'false'}
                                        onChange={(e) => editForm.setData('is_active', e.target.value === 'true')}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500"
                                    >
                                        <option value="true">Ativo</option>
                                        <option value="false">Inativo</option>
                                    </select>
                                </div>
                            </div>

                            <div className="pt-3 border-t border-slate-800 flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => setEditingPlan(null)}
                                    className="px-4 py-2 rounded-xl text-slate-400 hover:text-white text-sm"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={editForm.processing}
                                    className="px-5 py-2 bg-amber-600 hover:bg-amber-500 text-slate-950 font-bold rounded-xl text-sm transition-colors shadow-lg shadow-amber-600/20"
                                >
                                    {editForm.processing ? 'Salvando...' : 'Salvar Alterações'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </LandlordLayout>
    );
}
