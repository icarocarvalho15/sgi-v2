import React from 'react';
import { Head, router } from '@inertiajs/react';
import { Tenant } from '@/types';
import { Building2, ArrowRight, ShieldCheck, LogOut } from 'lucide-react';

interface Props {
    tenants: Tenant[];
    currentTenantId?: string | null;
}

export default function SelectTenant({ tenants, currentTenantId }: Props) {
    const handleSelect = (tenantId: string) => {
        router.post(`/select-tenant/${tenantId}`);
    };

    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center p-6 font-sans">
            <Head title="Selecione sua Empresa - SGI v2.0" />

            <div className="w-full max-w-lg space-y-6">
                <div className="text-center space-y-2">
                    <div className="inline-flex w-12 h-12 rounded-2xl bg-indigo-600 items-center justify-center font-black text-xl text-white shadow-lg shadow-indigo-600/30">
                        SGI
                    </div>
                    <h1 className="text-2xl font-bold tracking-tight text-white">Selecione o Ambiente de Trabalho</h1>
                    <p className="text-xs text-slate-400">
                        Sua conta possui acesso a múltiplas indústrias cadastradas. Escolha onde deseja operar:
                    </p>
                </div>

                <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-3">
                    {tenants.map((t) => {
                        const isCurrent = t.id === currentTenantId;
                        return (
                            <button
                                key={t.id}
                                onClick={() => handleSelect(t.id)}
                                className={`w-full p-4 rounded-xl text-left border transition flex items-center justify-between group ${
                                    isCurrent
                                        ? 'bg-indigo-600/10 border-indigo-500/50 hover:bg-indigo-600/20'
                                        : 'bg-slate-950 border-slate-800 hover:border-slate-700 hover:bg-slate-800/40'
                                }`}
                            >
                                <div className="flex items-center space-x-3.5 overflow-hidden">
                                    <div className="w-10 h-10 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center text-indigo-400 shrink-0 group-hover:scale-105 transition">
                                        <Building2 className="w-5 h-5" />
                                    </div>
                                    <div className="truncate">
                                        <div className="flex items-center space-x-2">
                                            <h3 className="font-bold text-sm text-white truncate">{t.name}</h3>
                                            {isCurrent && (
                                                <span className="bg-indigo-500/20 text-indigo-400 text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                                    Ativo
                                                </span>
                                            )}
                                        </div>
                                        <p className="text-xs text-slate-400 font-mono">
                                            {t.subdomain}.sgi.local • Plano: {t.plan?.name || 'Standard'}
                                        </p>
                                    </div>
                                </div>

                                <ArrowRight className="w-4 h-4 text-slate-400 group-hover:text-indigo-400 group-hover:translate-x-1 transition shrink-0 ml-2" />
                            </button>
                        );
                    })}

                    <div className="pt-4 flex items-center justify-between border-t border-slate-800 text-xs text-slate-400">
                        <span className="flex items-center space-x-1">
                            <ShieldCheck className="w-4 h-4 text-emerald-400" />
                            <span>Isolamento RLS por Empresa</span>
                        </span>

                        <button
                            onClick={handleLogout}
                            className="hover:text-rose-400 flex items-center space-x-1 transition"
                        >
                            <LogOut className="w-3.5 h-3.5" />
                            <span>Trocar de Conta / Sair</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
