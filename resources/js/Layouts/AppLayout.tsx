import React, { ReactNode } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { 
    Users, 
    Kanban, 
    FileText, 
    Printer, 
    Sliders, 
    Layers, 
    Boxes,
    FileSpreadsheet,
    DollarSign,
    TrendingUp,
    Settings,
    ShieldCheck, 
    CheckCircle2, 
    AlertCircle,
    Building2,
    Palette,
    LogOut,
    LogIn,
    ShieldAlert,
    User
} from 'lucide-react';

interface Props {
    title?: string;
    children: ReactNode;
}

export default function AppLayout({ title, children }: Props) {
    const { flash, tenant, auth, impersonation } = usePage<any>().props;

    const navItems = [
        { label: 'Clientes & Fornecedores', href: '/crm/partners', icon: Users },
        { label: 'Funil de Vendas (Kanban)', href: '/crm/kanban', icon: Kanban },
        { label: 'Orçamentos Técnicos', href: '/quotes', icon: FileText },
        { label: 'Fichas Técnicas (BOM)', href: '/production/boms', icon: Layers },
        { label: 'Chão de Fábrica (OPs)', href: '/production/orders', icon: Sliders },
        { label: 'Estoque & Saldos', href: '/inventory/balances', icon: Boxes },
        { label: 'Razão Kardex', href: '/inventory/kardex', icon: FileSpreadsheet },
        { label: 'Financeiro (Títulos)', href: '/finance/entries', icon: DollarSign },
        { label: 'Fluxo & DRE', href: '/finance/reports', icon: TrendingUp },
        { label: 'Config. Fiscais', href: '/settings/fiscal', icon: Settings },
        { label: 'Status Customizados', href: '/settings/statuses', icon: Palette },
    ];

    const isSuperAdmin = auth?.user?.is_super_admin;
    const isImpersonating = impersonation?.is_impersonating;

    const handleLogout = () => {
        router.post('/logout');
    };

    const handleStopImpersonation = () => {
        router.post('/admin/impersonate/stop');
    };

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col md:flex-row">
            {/* Sidebar */}
            <aside className="w-full md:w-64 bg-slate-900 border-r border-slate-800 flex flex-col shrink-0">
                <div className="p-6 border-b border-slate-800 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-white shadow-lg shadow-indigo-500/30">
                            <Printer className="w-5 h-5" />
                        </div>
                        <div>
                            <h1 className="font-bold text-base leading-tight tracking-tight text-white">SGI v2.0</h1>
                            <span className="text-xs text-indigo-400 font-medium tracking-wide">Gráficas & Brindes</span>
                        </div>
                    </div>
                </div>

                {/* Tenant Badge */}
                <div className="mx-4 my-3 px-3 py-2 bg-slate-800/60 rounded-lg border border-slate-700/50 flex items-center justify-between">
                    <div className="truncate">
                        <span className="text-[10px] uppercase font-semibold text-slate-400 tracking-wider block">Empresa Ativa</span>
                        <span className="text-sm font-medium text-slate-200 truncate block">
                            {tenant?.name || 'Gráfica Modelo'}
                        </span>
                    </div>
                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        RLS Ativo
                    </span>
                </div>

                {/* Navigation */}
                <nav className="p-4 space-y-1 flex-1 overflow-y-auto">
                    <span className="px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500 block mb-1">
                        Módulos Operacionais
                    </span>

                    {navItems.map((item) => {
                        const Icon = item.icon;
                        const active = window.location.pathname.startsWith(item.href);
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all ${
                                    active
                                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                                        : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60'
                                }`}
                            >
                                <Icon className={`w-4 h-4 ${active ? 'text-white' : 'text-slate-400'}`} />
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>

                <div className="p-4 border-t border-slate-800 text-xs text-slate-500 flex items-center justify-between">
                    <span className="flex items-center gap-1">
                        <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
                        Double-Lock Security
                    </span>
                    <span>v2.0</span>
                </div>
            </aside>

            {/* Main Content Area */}
            <main className="flex-1 flex flex-col min-w-0 overflow-y-auto">
                {/* Header Superior */}
                <header className="h-16 bg-slate-900/50 backdrop-blur border-b border-slate-800/80 px-6 flex items-center justify-between sticky top-0 z-10">
                    <div className="flex items-center gap-3">
                        <h2 className="text-lg font-semibold text-white tracking-tight">{title || 'Visão Geral'}</h2>
                        <span className="hidden sm:inline-block text-xs text-slate-400 bg-slate-800/80 px-2.5 py-1 rounded-full border border-slate-700/60">
                            Zero-Float Mode (bcmath)
                        </span>
                    </div>

                    {/* Área do Usuário Logado & Ações */}
                    <div className="flex items-center gap-4">
                        {auth?.user ? (
                            <div className="flex items-center gap-3">
                                {isImpersonating && (
                                    <button
                                        onClick={handleStopImpersonation}
                                        className="hidden md:flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 hover:bg-amber-500/30 transition-colors"
                                    >
                                        <LogOut className="w-3.5 h-3.5" />
                                        Voltar ao Landlord
                                    </button>
                                )}

                                <div className="flex items-center gap-2.5 pl-2 border-l border-slate-800">
                                    <div className="w-8 h-8 rounded-full bg-indigo-600/30 border border-indigo-500/40 text-indigo-300 flex items-center justify-center font-bold text-xs uppercase">
                                        {auth.user.name?.slice(0, 2) || 'US'}
                                    </div>
                                    <div className="hidden sm:block text-left">
                                        <span className="text-xs font-semibold text-white block leading-tight">
                                            {auth.user.name}
                                        </span>
                                        <span className="text-[10px] text-slate-400 block font-mono">
                                            {isSuperAdmin ? 'SuperAdmin SaaS' : auth.user.email}
                                        </span>
                                    </div>
                                </div>

                                <button
                                    onClick={handleLogout}
                                    title="Encerrar Sessão (Logout)"
                                    className="p-2 rounded-lg bg-slate-800/80 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 border border-slate-700/60 transition-all flex items-center gap-1 text-xs"
                                >
                                    <LogOut className="w-4 h-4" />
                                    <span className="hidden md:inline">Sair</span>
                                </button>
                            </div>
                        ) : (
                            <Link
                                href="/login"
                                className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl flex items-center gap-1.5 transition-colors shadow-lg shadow-indigo-600/20"
                            >
                                <LogIn className="w-3.5 h-3.5" />
                                Entrar / Login
                            </Link>
                        )}
                    </div>
                </header>

                {/* Banner de Personificação Ativa (SuperAdmin Impersonation Mode) */}
                {isImpersonating && (
                    <div className="bg-amber-500/15 border-b border-amber-500/30 px-6 py-2.5 flex items-center justify-between text-amber-300 text-xs sm:text-sm">
                        <div className="flex items-center gap-2.5">
                            <ShieldAlert className="w-5 h-5 text-amber-400 shrink-0" />
                            <span>
                                Modo de <strong>Personificação Técnica Auditada</strong> ativo na empresa{' '}
                                <strong className="text-white underline">{impersonation?.target_tenant?.name || tenant?.name}</strong>.
                            </span>
                        </div>
                        <button
                            onClick={handleStopImpersonation}
                            className="px-3 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-lg text-xs flex items-center gap-1.5 transition-colors shrink-0 shadow-md"
                        >
                            <LogOut className="w-3.5 h-3.5" />
                            Encerrar Personificação
                        </button>
                    </div>
                )}

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="m-6 mb-0 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-center gap-3">
                        <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0" />
                        <span>{flash.success}</span>
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
