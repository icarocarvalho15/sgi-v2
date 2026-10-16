import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import CentralAdminLayout from '@/Layouts/CentralAdminLayout';
import { Tenant, ImpersonationLog } from '@/types';
import { 
    Building2, 
    TrendingUp, 
    Users, 
    AlertCircle, 
    ShieldCheck, 
    UserCheck,
    ArrowUpRight,
    Search
} from 'lucide-react';

interface Props {
    metrics: {
        total_tenants: number;
        active_tenants: number;
        suspended_tenants: number;
        trial_tenants: number;
        total_users: number;
        estimated_mrr: number;
    };
    recentTenants: Tenant[];
    recentImpersonations: ImpersonationLog[];
}

export default function Dashboard({ metrics, recentTenants, recentImpersonations }: Props) {
    const [selectedTenantId, setSelectedTenantId] = useState<string | null>(null);
    const [reason, setReason] = useState('');
    const [modalOpen, setModalOpen] = useState(false);

    const openImpersonationModal = (tenantId: string) => {
        setSelectedTenantId(tenantId);
        setReason('');
        setModalOpen(true);
    };

    const handleImpersonate = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedTenantId || !reason.trim()) return;

        router.post(`/admin/impersonate/${selectedTenantId}`, {
            reason: reason.trim(),
        }, {
            onSuccess: () => setModalOpen(false),
        });
    };

    const formatCurrency = (val: number) => {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
    };

    return (
        <CentralAdminLayout title="Dashboard SaaS">
            <Head title="Painel Central Landlord - SGI v2.0" />

            <div className="space-y-8">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-white tracking-tight">Painel de Controle SaaS</h1>
                        <p className="text-sm text-slate-400 mt-1">
                            Visão panorâmica da operadora: clientes industriais, métricas de receita e integridade.
                        </p>
                    </div>

                    <div className="flex items-center space-x-3">
                        <Link
                            href="/admin/tenants"
                            className="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-md shadow-indigo-600/20 flex items-center space-x-2"
                        >
                            <Building2 className="w-4 h-4" />
                            <span>Nova Empresa</span>
                        </Link>
                    </div>
                </div>

                {/* Grid de Métricas SaaS */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div className="flex items-center justify-between text-slate-400">
                            <span className="text-xs font-semibold uppercase tracking-wider">MRR Estimado</span>
                            <TrendingUp className="w-4 h-4 text-emerald-400" />
                        </div>
                        <p className="text-2xl font-black text-white mt-2">
                            {formatCurrency(metrics.estimated_mrr)}
                        </p>
                        <p className="text-[11px] text-emerald-400 mt-1 flex items-center space-x-1">
                            <span>Soma de planos ativos</span>
                        </p>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div className="flex items-center justify-between text-slate-400">
                            <span className="text-xs font-semibold uppercase tracking-wider">Empresas Ativas</span>
                            <Building2 className="w-4 h-4 text-indigo-400" />
                        </div>
                        <p className="text-2xl font-black text-white mt-2">
                            {metrics.active_tenants} <span className="text-sm font-normal text-slate-400">/ {metrics.total_tenants}</span>
                        </p>
                        <p className="text-[11px] text-slate-400 mt-1">
                            {metrics.suspended_tenants} suspensas • {metrics.trial_tenants} em trial
                        </p>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div className="flex items-center justify-between text-slate-400">
                            <span className="text-xs font-semibold uppercase tracking-wider">Usuários Operacionais</span>
                            <Users className="w-4 h-4 text-cyan-400" />
                        </div>
                        <p className="text-2xl font-black text-white mt-2">{metrics.total_users}</p>
                        <p className="text-[11px] text-slate-400 mt-1">Operadores, engenheiros e admins</p>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div className="flex items-center justify-between text-slate-400">
                            <span className="text-xs font-semibold uppercase tracking-wider">Blindagem Multi-Tenant</span>
                            <ShieldCheck className="w-4 h-4 text-emerald-400" />
                        </div>
                        <p className="text-sm font-bold text-emerald-400 mt-2">RLS + Fail-Closed Ativos</p>
                        <p className="text-[11px] text-slate-400 mt-1">Zero-Trust Key Derivation</p>
                    </div>
                </div>

                {/* Duas Colunas: Empresas Recentes & Logs de Impersonation */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {/* Empresas Recentes */}
                    <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                        <div className="p-5 border-b border-slate-800 flex items-center justify-between">
                            <h2 className="font-semibold text-white text-base">Empresas Recentes</h2>
                            <Link href="/admin/tenants" className="text-xs text-indigo-400 hover:text-indigo-300 transition flex items-center space-x-1">
                                <span>Ver todas</span>
                                <ArrowUpRight className="w-3.5 h-3.5" />
                            </Link>
                        </div>

                        <div className="divide-y divide-slate-800/80">
                            {recentTenants.length === 0 ? (
                                <p className="p-6 text-sm text-slate-400 text-center">Nenhuma empresa cadastrada.</p>
                            ) : (
                                recentTenants.map((t) => (
                                    <div key={t.id} className="p-4 flex items-center justify-between hover:bg-slate-800/40 transition">
                                        <div className="overflow-hidden pr-3">
                                            <p className="text-sm font-semibold text-slate-100 truncate">{t.name}</p>
                                            <p className="text-xs text-slate-400">
                                                {t.subdomain}.sgi.local • Plano: <span className="text-indigo-400">{t.plan?.name || 'N/A'}</span>
                                            </p>
                                        </div>

                                        <button
                                            onClick={() => openImpersonationModal(t.id)}
                                            className="bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 text-xs px-2.5 py-1.5 rounded font-medium transition shrink-0"
                                        >
                                            Impersonate
                                        </button>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>

                    {/* Logs Recentes de Auditoria de Suporte */}
                    <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                        <div className="p-5 border-b border-slate-800 flex items-center justify-between">
                            <h2 className="font-semibold text-white text-base">Auditoria de Suporte (Impersonation)</h2>
                            <span className="text-xs text-slate-400">Imutável</span>
                        </div>

                        <div className="divide-y divide-slate-800/80">
                            {recentImpersonations.length === 0 ? (
                                <p className="p-6 text-sm text-slate-400 text-center">Nenhum log de suporte registrado.</p>
                            ) : (
                                recentImpersonations.map((log) => (
                                    <div key={log.id} className="p-4 space-y-1 hover:bg-slate-800/40 transition">
                                        <div className="flex items-center justify-between text-xs">
                                            <span className="font-semibold text-slate-200">
                                                {log.impersonator?.name} → {log.tenant?.name}
                                            </span>
                                            <span className="text-slate-400">
                                                {new Date(log.started_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}
                                            </span>
                                        </div>
                                        <p className="text-xs text-slate-400 italic">"{log.reason}"</p>
                                        <p className="text-[10px] text-slate-400">IP: {log.ip_address || '127.0.0.1'}</p>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Modal de Confirmação de Impersonation */}
            {modalOpen && (
                <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-6 space-y-4 shadow-xl">
                        <div className="flex items-center space-x-3 text-amber-400">
                            <AlertCircle className="w-6 h-6 shrink-0" />
                            <h3 className="font-bold text-lg text-white">Iniciar Modo Suporte (Impersonation)</h3>
                        </div>

                        <p className="text-xs text-slate-400 leading-relaxed">
                            Por exigência das diretrizes de segurança, qualquer acesso ao ambiente de uma empresa cliente
                            deve ser justificado. Seu usuário, IP e motivo serão gravados na tabela imutável de auditoria.
                        </p>

                        <form onSubmit={handleImpersonate} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Motivo do Acesso <span className="text-rose-400">*</span>
                                </label>
                                <textarea
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                    placeholder="Ex: Atendimento ao chamado #1042 - Erro no cálculo de BOM..."
                                    required
                                    rows={3}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg p-2.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div className="flex items-center justify-end space-x-3 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setModalOpen(false)}
                                    className="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white transition"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={!reason.trim()}
                                    className="bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold px-4 py-2 rounded-lg transition disabled:opacity-50"
                                >
                                    Confirmar e Acessar Empresa
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </CentralAdminLayout>
    );
}
