import React, { ReactNode } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { 
    LayoutDashboard, 
    Building2, 
    CreditCard, 
    ShieldAlert, 
    LogOut, 
    ExternalLink,
    AlertTriangle,
    Layers,
    UserCheck
} from 'lucide-react';

interface Props {
    children: ReactNode;
    title?: string;
}

export default function CentralAdminLayout({ children, title }: Props) {
    const { props, url } = usePage<PageProps>();
    const { auth, flash } = props;

    const navItems = [
        { label: 'Dashboard SaaS', href: '/admin', icon: LayoutDashboard, active: url === '/admin' },
        { label: 'Empresas (Tenants)', href: '/admin/tenants', icon: Building2, active: url.startsWith('/admin/tenants') },
        { label: 'Planos & Preços', href: '/admin/plans', icon: CreditCard, active: url.startsWith('/admin/plans') },
    ];

    const handleLeaveImpersonation = () => {
        router.post('/admin/impersonate/leave');
    };

    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans">
            {/* Banner Crítico de Impersonation */}
            {auth.is_impersonating && (
                <div className="bg-amber-500 text-slate-950 px-4 py-2 text-sm font-semibold flex items-center justify-between shadow-lg sticky top-0 z-50 animate-pulse">
                    <div className="flex items-center space-x-2">
                        <AlertTriangle className="w-5 h-5 text-slate-950 shrink-0" />
                        <span>
                            MODO SUPORTE ATIVO: Visualizando contexto da empresa <strong>{auth.impersonated_tenant_name}</strong>
                        </span>
                    </div>
                    <button
                        onClick={handleLeaveImpersonation}
                        className="bg-slate-950 text-white text-xs px-3 py-1.5 rounded-md hover:bg-slate-800 transition flex items-center space-x-1"
                    >
                        <span>Encerrar Suporte</span>
                        <ExternalLink className="w-3.5 h-3.5" />
                    </button>
                </div>
            )}

            <div className="flex flex-1">
                {/* Sidebar Landlord */}
                <aside className="w-64 bg-slate-900 border-r border-slate-800 flex flex-col shrink-0">
                    <div className="p-5 border-b border-slate-800 flex items-center space-x-3">
                        <div className="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-white shadow-md shadow-indigo-600/30">
                            SGI
                        </div>
                        <div>
                            <div className="font-bold text-white tracking-wide text-sm flex items-center space-x-1.5">
                                <span>Core Admin</span>
                                <span className="bg-indigo-500/20 text-indigo-400 text-[10px] px-1.5 py-0.5 rounded font-mono font-medium">
                                    Landlord
                                </span>
                            </div>
                            <p className="text-xs text-slate-400">Gestão Global SaaS</p>
                        </div>
                    </div>

                    <div className="px-3 py-4 flex-1 space-y-1">
                        <div className="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Operações Globais
                        </div>
                        {navItems.map((item) => {
                            const Icon = item.icon;
                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={`flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition ${
                                        item.active
                                            ? 'bg-indigo-600 text-white shadow-sm'
                                            : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'
                                    }`}
                                >
                                    <Icon className="w-4 h-4 shrink-0" />
                                    <span>{item.label}</span>
                                </Link>
                            );
                        })}
                    </div>

                    {/* Perfil & Sair */}
                    <div className="p-4 border-t border-slate-800 flex items-center justify-between bg-slate-900/60">
                        <div className="flex items-center space-x-2.5 overflow-hidden">
                            <div className="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-xs font-bold text-indigo-400 shrink-0">
                                SA
                            </div>
                            <div className="truncate">
                                <p className="text-xs font-semibold text-slate-200 truncate">{auth.user?.name}</p>
                                <p className="text-[11px] text-slate-400 truncate">{auth.user?.email}</p>
                            </div>
                        </div>
                        <button
                            onClick={handleLogout}
                            title="Sair do Sistema"
                            className="text-slate-400 hover:text-rose-400 transition p-1.5 rounded-lg hover:bg-slate-800"
                        >
                            <LogOut className="w-4 h-4" />
                        </button>
                    </div>
                </aside>

                {/* Área de Conteúdo Principal */}
                <main className="flex-1 flex flex-col min-w-0 bg-slate-950">
                    {/* Topbar com Flash Messages */}
                    {flash.success && (
                        <div className="bg-emerald-500/10 border-b border-emerald-500/30 text-emerald-400 px-6 py-2.5 text-sm flex items-center justify-between">
                            <div className="flex items-center space-x-2">
                                <span className="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
                                <span>{flash.success}</span>
                            </div>
                        </div>
                    )}
                    {flash.error && (
                        <div className="bg-rose-500/10 border-b border-rose-500/30 text-rose-400 px-6 py-2.5 text-sm flex items-center justify-between">
                            <div className="flex items-center space-x-2">
                                <span className="inline-block w-2 h-2 rounded-full bg-rose-400"></span>
                                <span>{flash.error}</span>
                            </div>
                        </div>
                    )}

                    <div className="p-6 lg:p-8 flex-1 overflow-y-auto">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
