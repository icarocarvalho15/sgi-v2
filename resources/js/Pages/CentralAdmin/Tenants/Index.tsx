import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import CentralAdminLayout from '@/Layouts/CentralAdminLayout';
import { Tenant, Plan } from '@/types';
import { 
    Building2, 
    Plus, 
    Search, 
    Filter, 
    MoreHorizontal, 
    CheckCircle2, 
    XCircle, 
    AlertTriangle,
    ShieldAlert,
    ExternalLink
} from 'lucide-react';

interface Props {
    tenants: {
        data: Tenant[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
        total: number;
    };
    plans: Plan[];
    filters: {
        status?: string;
        search?: string;
    };
}

export default function TenantsIndex({ tenants, plans, filters }: Props) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [impersonateModalOpen, setImpersonateModalOpen] = useState(false);
    const [selectedTenant, setSelectedTenant] = useState<Tenant | null>(null);
    const [reason, setReason] = useState('');
    const [searchQuery, setSearchQuery] = useState(filters.search || '');

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        trade_name: '',
        document_number: '',
        subdomain: '',
        plan_id: plans[0]?.id || '',
        owner_name: '',
        owner_email: '',
        owner_password: 'password123',
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/tenants', { search: searchQuery, status: filters.status }, { preserveState: true });
    };

    const handleStatusFilter = (status: string | null) => {
        router.get('/admin/tenants', { status: status || undefined, search: searchQuery || undefined }, { preserveState: true });
    };

    const handleCreateTenant = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/tenants', {
            onSuccess: () => {
                setCreateModalOpen(false);
                reset();
            },
        });
    };

    const handleToggleStatus = (tenant: Tenant) => {
        const nextStatus = tenant.status === 'active' ? 'suspended' : 'active';
        if (confirm(`Tem certeza que deseja alterar o status da empresa "${tenant.name}" para ${nextStatus}?`)) {
            router.patch(`/admin/tenants/${tenant.id}/status`, { status: nextStatus });
        }
    };

    const openImpersonateModal = (tenant: Tenant) => {
        setSelectedTenant(tenant);
        setReason('');
        setImpersonateModalOpen(true);
    };

    const handleConfirmImpersonate = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedTenant || !reason.trim()) return;

        router.post(`/admin/impersonate/${selectedTenant.id}`, {
            reason: reason.trim(),
        });
    };

    return (
        <CentralAdminLayout title="Empresas (Tenants)">
            <Head title="Gestão de Tenants - SGI v2.0" />

            <div className="space-y-6">
                {/* Topo: Título e Botão Criar */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-white tracking-tight">Empresas & Clientes (Tenants)</h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Provisionamento atômico de novos inquilinos industriais, monitoramento e suspensão de acesso.
                        </p>
                    </div>

                    <button
                        onClick={() => setCreateModalOpen(true)}
                        className="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-md shadow-indigo-600/20 flex items-center space-x-2 shrink-0"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Provisionar Nova Empresa</span>
                    </button>
                </div>

                {/* Filtros e Busca */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                    <form onSubmit={handleSearch} className="flex-1 max-w-md w-full relative">
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Buscar por nome, subdomínio ou CNPJ..."
                            className="w-full bg-slate-950 border border-slate-700/80 rounded-lg pl-9 pr-4 py-2 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"
                        />
                        <Search className="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                    </form>

                    <div className="flex items-center space-x-2 self-start md:self-auto overflow-x-auto w-full md:w-auto">
                        <button
                            onClick={() => handleStatusFilter(null)}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${
                                !filters.status ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                            }`}
                        >
                            Todos ({tenants.total})
                        </button>
                        <button
                            onClick={() => handleStatusFilter('active')}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${
                                filters.status === 'active' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                            }`}
                        >
                            Ativos
                        </button>
                        <button
                            onClick={() => handleStatusFilter('suspended')}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${
                                filters.status === 'suspended' ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                            }`}
                        >
                            Suspensos
                        </button>
                    </div>
                </div>

                {/* Tabela de Tenants */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-950 text-slate-400 uppercase font-semibold tracking-wider border-b border-slate-800">
                                <tr>
                                    <th className="px-5 py-3.5">Empresa</th>
                                    <th className="px-5 py-3.5">Subdomínio</th>
                                    <th className="px-5 py-3.5">Plano Contratado</th>
                                    <th className="px-5 py-3.5">Usuários</th>
                                    <th className="px-5 py-3.5">Status</th>
                                    <th className="px-5 py-3.5 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/80 text-slate-300">
                                {tenants.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-5 py-8 text-center text-slate-400">
                                            Nenhuma empresa encontrada com os filtros selecionados.
                                        </td>
                                    </tr>
                                ) : (
                                    tenants.data.map((tenant) => (
                                        <tr key={tenant.id} className="hover:bg-slate-800/40 transition">
                                            <td className="px-5 py-4">
                                                <div className="font-semibold text-white">{tenant.name}</div>
                                                {tenant.document_number && (
                                                    <div className="text-[11px] text-slate-400">{tenant.document_number}</div>
                                                )}
                                            </td>
                                            <td className="px-5 py-4 font-mono text-slate-400">
                                                {tenant.subdomain}.sgi.local
                                            </td>
                                            <td className="px-5 py-4">
                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                                    {tenant.plan?.name || 'Sem plano'}
                                                </span>
                                            </td>
                                            <td className="px-5 py-4 font-medium text-slate-200">
                                                {tenant.users_count || 0}
                                            </td>
                                            <td className="px-5 py-4">
                                                <span className={`inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold ${
                                                    tenant.status === 'active' 
                                                        ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' 
                                                        : tenant.status === 'suspended'
                                                        ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20'
                                                        : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'
                                                }`}>
                                                    {tenant.status.toUpperCase()}
                                                </span>
                                            </td>
                                            <td className="px-5 py-4 text-right space-x-2">
                                                <button
                                                    onClick={() => openImpersonateModal(tenant)}
                                                    className="bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 text-xs px-2.5 py-1.5 rounded font-medium border border-amber-500/20 transition"
                                                    title="Acessar painel como esta empresa"
                                                >
                                                    Impersonate
                                                </button>

                                                <button
                                                    onClick={() => handleToggleStatus(tenant)}
                                                    className={`text-xs px-2.5 py-1.5 rounded font-medium transition ${
                                                        tenant.status === 'active'
                                                            ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20'
                                                            : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20'
                                                    }`}
                                                >
                                                    {tenant.status === 'active' ? 'Suspender' : 'Ativar'}
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Modal de Provisionamento de Nova Empresa */}
            {createModalOpen && (
                <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-6 space-y-4 shadow-xl max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="font-bold text-lg text-white">Provisionar Nova Empresa (Tenant)</h3>
                            <button onClick={() => setCreateModalOpen(false)} className="text-slate-400 hover:text-white">✕</button>
                        </div>

                        <form onSubmit={handleCreateTenant} className="space-y-4 text-xs">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label className="block font-semibold text-slate-300 mb-1">Razão Social *</label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                        placeholder="Ex: Metalúrgica Delta Ltda"
                                        className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white placeholder-slate-400"
                                    />
                                    {errors.name && <span className="text-rose-400 text-[11px]">{errors.name}</span>}
                                </div>

                                <div>
                                    <label className="block font-semibold text-slate-300 mb-1">Nome Fantasia</label>
                                    <input
                                        type="text"
                                        value={data.trade_name}
                                        onChange={(e) => setData('trade_name', e.target.value)}
                                        placeholder="Ex: Delta Usinagem"
                                        className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white placeholder-slate-400"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label className="block font-semibold text-slate-300 mb-1">CNPJ / CPF</label>
                                    <input
                                        type="text"
                                        value={data.document_number}
                                        onChange={(e) => setData('document_number', e.target.value)}
                                        placeholder="00.000.000/0001-00"
                                        className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white placeholder-slate-400"
                                    />
                                </div>

                                <div>
                                    <label className="block font-semibold text-slate-300 mb-1">Subdomínio *</label>
                                    <div className="flex items-center">
                                        <input
                                            type="text"
                                            value={data.subdomain}
                                            onChange={(e) => setData('subdomain', e.target.value)}
                                            required
                                            placeholder="delta"
                                            className="w-full bg-slate-950 border border-slate-700 rounded-l p-2 text-white placeholder-slate-400"
                                        />
                                        <span className="bg-slate-800 border border-l-0 border-slate-700 px-2 py-2 text-slate-400 rounded-r text-[11px]">
                                            .sgi.local
                                        </span>
                                    </div>
                                    {errors.subdomain && <span className="text-rose-400 text-[11px]">{errors.subdomain}</span>}
                                </div>
                            </div>

                            <div>
                                <label className="block font-semibold text-slate-300 mb-1">Plano Inicial *</label>
                                <select
                                    value={data.plan_id}
                                    onChange={(e) => setData('plan_id', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                                >
                                    {plans.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name} - R$ {Number(p.price).toFixed(2)}/mês
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="border-t border-slate-800 pt-3">
                                <h4 className="font-bold text-slate-200 mb-2">Dados do Usuário Administrador (Dono da Conta)</h4>
                                <div className="space-y-3">
                                    <div>
                                        <label className="block font-semibold text-slate-300 mb-1">Nome Completo *</label>
                                        <input
                                            type="text"
                                            value={data.owner_name}
                                            onChange={(e) => setData('owner_name', e.target.value)}
                                            required
                                            placeholder="Ex: João da Silva"
                                            className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                                        />
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label className="block font-semibold text-slate-300 mb-1">E-mail de Acesso *</label>
                                            <input
                                                type="email"
                                                value={data.owner_email}
                                                onChange={(e) => setData('owner_email', e.target.value)}
                                                required
                                                placeholder="admin@delta.com.br"
                                                className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                                            />
                                            {errors.owner_email && <span className="text-rose-400 text-[11px]">{errors.owner_email}</span>}
                                        </div>

                                        <div>
                                            <label className="block font-semibold text-slate-300 mb-1">Senha Inicial *</label>
                                            <input
                                                type="password"
                                                value={data.owner_password}
                                                onChange={(e) => setData('owner_password', e.target.value)}
                                                required
                                                className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setCreateModalOpen(false)}
                                    className="px-4 py-2 text-slate-400 hover:text-white"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-indigo-600 hover:bg-indigo-500 text-white font-bold px-4 py-2 rounded transition disabled:opacity-50"
                                >
                                    {processing ? 'Provisionando...' : 'Criar Empresa'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal de Impersonation */}
            {impersonateModalOpen && selectedTenant && (
                <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-6 space-y-4 shadow-xl">
                        <div className="flex items-center space-x-3 text-amber-400">
                            <ShieldAlert className="w-6 h-6 shrink-0" />
                            <h3 className="font-bold text-lg text-white">Acessar {selectedTenant.name}</h3>
                        </div>

                        <p className="text-xs text-slate-400 leading-relaxed">
                            Informe o motivo de suporte obrigatório para acessar o ERP desta empresa.
                        </p>

                        <form onSubmit={handleConfirmImpersonate} className="space-y-4">
                            <div>
                                <textarea
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                    placeholder="Motivo detalhado do acesso de suporte..."
                                    required
                                    rows={3}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg p-2.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div className="flex items-center justify-end space-x-3">
                                <button
                                    type="button"
                                    onClick={() => setImpersonateModalOpen(false)}
                                    className="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={!reason.trim()}
                                    className="bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold px-4 py-2 rounded-lg transition disabled:opacity-50"
                                >
                                    Entrar no Ambiente
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </CentralAdminLayout>
    );
}
