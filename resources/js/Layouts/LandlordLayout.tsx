import React, { ReactNode } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { 
    Building2, 
    CreditCard, 
    TrendingUp, 
    ShieldAlert, 
    LogOut, 
    ShieldCheck, 
    CheckCircle2, 
    AlertCircle,
    Server,
    Layers,
    Info
} from 'lucide-react';

interface Props {
    title?: string;
    children: ReactNode;
}

export default function LandlordLayout({ title, children }: Props) {
    const { flash, auth } = usePage<any>().props;

    const navItems = [
        { label: 'Empresas & Assinaturas', href: '/admin/tenants', icon: Building2 },
        { label: 'Planos SaaS', href: '/admin/plans', icon: CreditCard },
        { label: 'Métricas de Negócio', href: '/admin/metrics', icon: TrendingUp },
        { label: 'Auditoria & Compliance', href: '/admin/audit-logs', icon: ShieldAlert },
    ];

    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col md:flex-row">
            {/* Sidebar do Landlord */}
            <aside className="w-full md:w-64 bg-slate-900 border-r border-slate-800 flex flex-col shrink-0">
                <div className="p-6 border-b border-slate-800 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center font-bold text-amber-400 shadow-lg shadow-amber-500/10">
                            <Server className="w-5 h-5" />
                        </div>
                        <div>
                            <h1 className="font-bold text-base leading-tight tracking-tight text-white flex items-center gap-1.5">
                                SGI Landlord
                            </h1>
                            <span className="text-xs text-amber-400 font-semibold tracking-wide">Administração SaaS</span>
                        </div>
                    </div>
                </div>

                {/* Badge de Nível de Acesso */}
                <div className="mx-4 my-3 px-3.5 py-2.5 bg-amber-500/10 rounded-xl border border-amber-500/20 flex items-center justify-between">
                    <div>
                        <span className="text-[10px] uppercase font-bold text-amber-400 tracking-wider block">Contexto Master</span>
                        <span className="text-xs font-semibold text-slate-200">
                            Sem Tenant Vinculado
                        </span>
                    </div>
                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        SuperAdmin
                    </span>
                </div>

                {/* Navegação Exclusiva do Landlord */}
                <nav className="p-4 space-y-1.5 flex-1">
                    <span className="px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500 block mb-2">
                        Gestão do SaaS
                    </span>

                    {navItems.map((item) => {
                        const Icon = item.icon;
                        const active = window.location.pathname.startsWith(item.href);
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all ${
                                    active
                                        ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20'
                                        : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60'
                                }`}
                            >
                                <Icon className={`w-4 h-4 ${active ? 'text-white' : 'text-slate-400'}`} />
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>

                <div className="p-4 border-t border-slate-800 text-xs text-slate-500 flex flex-col gap-1">
                    <div className="flex items-center justify-between">
                        <span className="flex items-center gap-1.5 text-slate-400 font-medium">
                            <ShieldCheck className="w-4 h-4 text-emerald-400" />
                            Multi-Tenant Master
                        </span>
                        <span className="font-mono text-[11px] text-slate-500">v2.0 SaaS</span>
                    </div>
                    <span className="text-[10px] text-slate-600">
                        Isolamento PostgreSQL RLS Central
                    </span>
                </div>
            </aside>

            {/* Main Content Area */}
            <main className="flex-1 flex flex-col min-w-0 overflow-y-auto">
                {/* Header Superior do Landlord */}
                <header className="h-16 bg-slate-900/60 backdrop-blur border-b border-slate-800/80 px-6 flex items-center justify-between sticky top-0 z-10">
                    <div className="flex items-center gap-3">
                        <h2 className="text-lg font-bold text-white tracking-tight">{title || 'Visão Central'}</h2>
                        <span className="hidden sm:inline-block text-xs font-semibold text-amber-300 bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/30">
                            Ambiente Global (Sem Tenant)
                        </span>
                    </div>

                    {/* Perfil SuperAdmin & Logout */}
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-300 flex items-center justify-center font-bold text-xs uppercase">
                                SA
                            </div>
                            <div className="hidden sm:block text-left">
                                <span className="text-xs font-bold text-white block leading-tight">
                                    {auth?.user?.name || 'SuperAdmin Landlord'}
                                </span>
                                <span className="text-[10px] text-amber-400/80 block font-mono">
                                    {auth?.user?.email || 'admin@sgi-erp.com'}
                                </span>
                            </div>

                            <button
                                onClick={handleLogout}
                                title="Encerrar Sessão (Logout)"
                                className="p-2 rounded-xl bg-slate-800/80 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 border border-slate-700/60 transition-all flex items-center gap-1.5 text-xs font-semibold ml-2"
                            >
                                <LogOut className="w-4 h-4" />
                                <span className="hidden md:inline">Sair</span>
                            </button>
                        </div>
                    </div>
                </header>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="m-6 mb-0 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-center gap-3">
                        <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0" />
                        <span>{flash.success}</span>
                    </div>
                )}
                {flash?.info && (
                    <div className="m-6 mb-0 p-4 rounded-xl bg-sky-500/10 border border-sky-500/30 text-sky-300 text-sm flex items-center gap-3">
                        <Info className="w-5 h-5 text-sky-400 shrink-0" />
                        <span>{flash.info}</span>
                    </div>
                )}
                {flash?.error && (
                    <div className="m-6 mb-0 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm flex items-center gap-3">
                        <AlertCircle className="w-5 h-5 text-rose-400 shrink-0" />
                        <span>{flash.error}</span>
                    </div>
                )}

                {/* Page Content */}
                <div className="p-6">{children}</div>
            </main>
        </div>
    );
}
