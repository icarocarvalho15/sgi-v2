import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { 
    Building2, 
    Plus, 
    ShieldAlert, 
    LogIn, 
    LogOut, 
    CheckCircle, 
    Clock, 
    Globe, 
    Users, 
    FileText, 
    Activity,
    ExternalLink
} from 'lucide-react';

interface Plan {
    id: string;
    name: string;
    code: string;
    price: string | number;
}

interface Tenant {
    id: string;
    name: string;
    subdomain: string;
    status: string;
    created_at: string;
    users_count: number;
    plan?: Plan;
}

interface ImpersonationLog {
    id: string;
    reason: string;
    ip_address: string | null;
    started_at: string;
    ended_at: string | null;
    user: {
        id: string;
        name: string;
        email: string;
    };
    tenant: {
        id: string;
        name: string;
        subdomain: string;
    };
}

interface Props {
    tenants: Tenant[];
    plans: Plan[];
    recentLogs: ImpersonationLog[];
    isImpersonating: boolean;
    impersonatedTenant?: Tenant | null;
}

export default function TenantsIndex({ tenants, plans, recentLogs, isImpersonating, impersonatedTenant }: Props) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [selectedTenantToImpersonate, setSelectedTenantToImpersonate] = useState<Tenant | null>(null);

    // Form para criar novo Tenant
    const createForm = useForm({
        name: '',
        subdomain: '',
        plan_id: plans[0]?.id || '',
    });

    // Form para impersonar
    const impersonateForm = useForm({
        reason: 'Atendimento de suporte técnico e auditoria operacional',
    });

    const handleCreateTenant = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/admin/tenants', {
            onSuccess: () => {
                setIsCreateOpen(false);
                createForm.reset();
            },
        });
    };

    const handleImpersonate = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedTenantToImpersonate) return;

        impersonateForm.post(`/admin/tenants/${selectedTenantToImpersonate.id}/impersonate`, {
            onSuccess: () => {
                setSelectedTenantToImpersonate(null);
            },
        });
    };

    const handleStopImpersonation = () => {
        router.post('/admin/impersonate/stop');
    };

    return (
        <AppLayout title="Painel Landlord SaaS (SuperAdmin)">
            <Head title="Landlord - Gestão de Tenants & Impersonation" />

            <div className="space-y-8">
                {/* Header do Landlord */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center gap-1">
                                <ShieldAlert className="w-3 h-3" /> Nível Landlord SuperAdmin
                            </span>
                        </div>
                        <h1 className="text-2xl font-bold text-white tracking-tight">Gestão Central de Empresas & Assinaturas</h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Visão global do ecossistema SaaS de gráficas e personificação técnica auditada (Zero-Trust).
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        {isImpersonating && (
                            <button
                                onClick={handleStopImpersonation}
                                className="px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold rounded-xl flex items-center gap-2 transition-colors shadow-lg shadow-rose-600/20"
                            >
                                <LogOut className="w-4 h-4" />
                                Encerrar Personificação de [{impersonatedTenant?.name}]
                            </button>
                        )}
                        <button
                            onClick={() => setIsCreateOpen(true)}
                            className="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl flex items-center gap-2 transition-colors shadow-lg shadow-indigo-600/20"
                        >
                            <Plus className="w-4 h-4" />
                            Cadastrar Nova Gráfica / Tenant
                        </button>
                    </div>
                </div>

                {/* Métricas do SaaS */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div className="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                            <Building2 className="w-6 h-6" />
                        </div>
                        <div>
                            <span className="text-xs font-medium text-slate-400 uppercase tracking-wider block">Total de Empresas</span>
                            <span className="text-2xl font-bold text-white">{tenants.length}</span>
                        </div>
                    </div>

                    <div className="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-emerald-600/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <Users className="w-6 h-6" />
                        </div>
                        <div>
                            <span className="text-xs font-medium text-slate-400 uppercase tracking-wider block">Usuários Ativos no ERP</span>
                            <span className="text-2xl font-bold text-white">
                                {tenants.reduce((acc, t) => acc + (t.users_count || 0), 0)}
                            </span>
                        </div>
                    </div>

                    <div className="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-amber-600/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <Activity className="w-6 h-6" />
                        </div>
                        <div>
                            <span className="text-xs font-medium text-slate-400 uppercase tracking-wider block">Sessões de Auditoria</span>
                            <span className="text-2xl font-bold text-white">{recentLogs.length}</span>
                        </div>
                    </div>
                </div>

                {/* Tabela de Tenants */}
                <div className="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
                    <div className="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                        <h2 className="font-semibold text-white text-base flex items-center gap-2">
                            <Building2 className="w-4 h-4 text-indigo-400" />
                            Empresas Assinantes Cadastradas
                        </h2>
                        <span className="text-xs text-slate-400">Multi-Tenancy RLS Isolado</span>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-300">
                            <thead className="bg-slate-950/50 text-slate-400 uppercase text-[11px] font-semibold tracking-wider border-b border-slate-800">
                                <tr>
                                    <th className="px-6 py-3.5">Empresa / Razão Social</th>
                                    <th className="px-6 py-3.5">Subdomínio SaaS</th>
                                    <th className="px-6 py-3.5">Plano Contratado</th>
                                    <th className="px-6 py-3.5 text-center">Usuários</th>
                                    <th className="px-6 py-3.5">Status</th>
                                    <th className="px-6 py-3.5">Data de Criação</th>
                                    <th className="px-6 py-3.5 text-right">Ação de Suporte</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60 font-medium">
                                {tenants.map((t) => (
                                    <tr key={t.id} className="hover:bg-slate-800/40 transition-colors">
                                        <td className="px-6 py-4 font-semibold text-white flex items-center gap-2.5">
                                            <div className="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-xs uppercase">
                                                {t.name.slice(0, 2)}
                                            </div>
                                            <span>{t.name}</span>
                                        </td>
                                        <td className="px-6 py-4 text-slate-400 font-mono text-xs">
                                            <span className="text-indigo-400 font-medium">{t.subdomain}</span>.sgi-erp.com
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className="px-2.5 py-1 rounded-md text-xs font-medium bg-slate-800 border border-slate-700 text-slate-200">
                                                {t.plan?.name || 'Gráfica Starter'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            <span className="inline-flex items-center gap-1 text-slate-300 text-xs">
                                                <Users className="w-3.5 h-3.5 text-slate-500" />
                                                {t.users_count}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                <CheckCircle className="w-3 h-3" /> Ativo
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-xs text-slate-400">
                                            {new Date(t.created_at).toLocaleDateString('pt-BR')}
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <button
                                                onClick={() => setSelectedTenantToImpersonate(t)}
                                                className="px-3 py-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 text-xs font-semibold flex items-center gap-1.5 ml-auto transition-all"
                                                title="Acessar o ERP no contexto deste Tenant com registro auditado de suporte"
                                            >
                                                <LogIn className="w-3.5 h-3.5" />
                                                Personificar
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Histórico de Impersonation (Compliance Audit Log) */}
                <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <div className="flex items-center justify-between border-b border-slate-800 pb-4">
                        <div className="flex items-center gap-2">
                            <ShieldAlert className="w-5 h-5 text-amber-400" />
                            <h2 className="text-base font-semibold text-white">Log de Auditoria de Personificação (Compliance)</h2>
                        </div>
                        <span className="text-xs text-slate-400">Imutável e Rastreável por IP/Horário</span>
                    </div>

                    {recentLogs.length === 0 ? (
                        <p className="text-sm text-slate-500 text-center py-6">Nenhuma sessão de impersonation registrada recentemente.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs text-slate-300">
                                <thead className="bg-slate-950/50 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                                    <tr>
                                        <th className="px-4 py-3">SuperAdmin (Operador)</th>
                                        <th className="px-4 py-3">Tenant Acessado</th>
                                        <th className="px-4 py-3">Motivo / Chamado</th>
                                        <th className="px-4 py-3">IP</th>
                                        <th className="px-4 py-3">Início</th>
                                        <th className="px-4 py-3">Fim / Duração</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-800/50 font-mono">
                                    {recentLogs.map((log) => (
                                        <tr key={log.id} className="hover:bg-slate-800/30">
                                            <td className="px-4 py-3 font-sans font-medium text-slate-200">
                                                {log.user?.name} ({log.user?.email})
                                            </td>
                                            <td className="px-4 py-3 font-sans text-indigo-400 font-semibold">
                                                {log.tenant?.name}
                                            </td>
                                            <td className="px-4 py-3 font-sans text-slate-400 max-w-xs truncate">
                                                {log.reason}
                                            </td>
                                            <td className="px-4 py-3 text-slate-400">
                                                {log.ip_address || '127.0.0.1'}
                                            </td>
                                            <td className="px-4 py-3 text-slate-400">
                                                {new Date(log.started_at).toLocaleString('pt-BR')}
                                            </td>
                                            <td className="px-4 py-3">
                                                {log.ended_at ? (
                                                    <span className="text-slate-400">{new Date(log.ended_at).toLocaleTimeString('pt-BR')}</span>
                                                ) : (
                                                    <span className="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20 animate-pulse">
                                                        Em Aberto
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal: Novo Tenant */}
            {isCreateOpen && (
                <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                <Building2 className="w-5 h-5 text-indigo-400" />
                                Cadastrar Nova Empresa / Gráfica
                            </h3>
                            <button
                                onClick={() => setIsCreateOpen(false)}
                                className="text-slate-400 hover:text-white text-sm"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleCreateTenant} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                    Nome da Gráfica / Razão Social
                                </label>
                                <input
                                    type="text"
                                    required
                                    placeholder="Ex: Gráfica & Brindes Progresso Ltda"
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
                                    Subdomínio do Tenant
                                </label>
                                <div className="flex items-center">
                                    <input
                                        type="text"
                                        required
                                        placeholder="grafica-progresso"
                                        value={createForm.data.subdomain}
                                        onChange={(e) => createForm.setData('subdomain', e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-l-xl px-3.5 py-2.5 text-sm font-mono text-white focus:outline-none focus:border-indigo-500"
                                    />
                                    <span className="bg-slate-800 border border-l-0 border-slate-700 rounded-r-xl px-3 py-2.5 text-xs text-slate-400 font-mono">
                                        .sgi-erp.com
                                    </span>
                                </div>
                                {createForm.errors.subdomain && (
                                    <span className="text-xs text-rose-400 mt-1 block">{createForm.errors.subdomain}</span>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                    Plano SaaS
                                </label>
                                <select
                                    value={createForm.data.plan_id}
                                    onChange={(e) => createForm.setData('plan_id', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="">Nenhum plano específico</option>
                                    {plans.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name} - R$ {Number(p.price).toFixed(2)}/mês
                                        </option>
                                    ))}
                                </select>
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
                                    {createForm.processing ? 'Criando...' : 'Criar Empresa'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal: Personificar (Impersonate) com Justificativa */}
            {selectedTenantToImpersonate && (
                <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-amber-500/30 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                <ShieldAlert className="w-5 h-5 text-amber-400" />
                                Auditoria de Suporte (Impersonation)
                            </h3>
                            <button
                                onClick={() => setSelectedTenantToImpersonate(null)}
                                className="text-slate-400 hover:text-white text-sm"
                            >
                                ✕
                            </button>
                        </div>

                        <div className="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3.5 text-xs text-amber-300">
                            Você está prestes a personificar a empresa <strong className="text-white font-semibold">[{selectedTenantToImpersonate.name}]</strong>.
                            O isolamento RLS do PostgreSQL será direcionado para este tenant e todas as operações serão vinculadas à sua credencial com registro de IP e horário.
                        </div>

                        <form onSubmit={handleImpersonate} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold uppercase text-slate-300 tracking-wider mb-1.5">
                                    Justificativa do Chamado de Suporte *
                                </label>
                                <textarea
                                    required
                                    rows={3}
                                    value={impersonateForm.data.reason}
                                    onChange={(e) => impersonateForm.setData('reason', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-indigo-500"
                                    placeholder="Ex: Chamado #8492 - Ajuste no cadastro de chapas CTP e teste de fluxo de caixa"
                                />
                                {impersonateForm.errors.reason && (
                                    <span className="text-xs text-rose-400 mt-1 block">{impersonateForm.errors.reason}</span>
                                )}
                            </div>

                            <div className="pt-3 border-t border-slate-800 flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => setSelectedTenantToImpersonate(null)}
                                    className="px-4 py-2 rounded-xl text-slate-400 hover:text-white text-sm"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={impersonateForm.processing}
                                    className="px-5 py-2 bg-amber-600 hover:bg-amber-500 text-white font-semibold rounded-xl text-sm transition-colors shadow-lg shadow-amber-600/20 flex items-center gap-2"
                                >
                                    <LogIn className="w-4 h-4" />
                                    {impersonateForm.processing ? 'Iniciando...' : 'Entrar como Tenant'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
