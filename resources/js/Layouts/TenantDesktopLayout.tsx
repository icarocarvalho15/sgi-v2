import React, { ReactNode, useState, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { 
    LayoutDashboard, 
    Boxes, 
    Cpu, 
    Factory, 
    Users, 
    Coins, 
    ReceiptText, 
    Lock, 
    LogOut, 
    Building2, 
    ChevronDown, 
    Search,
    AlertTriangle,
    Smartphone
} from 'lucide-react';

interface Props {
    children: ReactNode;
    title?: string;
}

export default function TenantDesktopLayout({ children, title }: Props) {
    const { props, url } = usePage<PageProps>();
    const { auth, flash } = props;
    const [commandPaletteOpen, setCommandPaletteOpen] = useState(false);

    const planFeatures = auth.tenant?.plan?.features || {};

    const modules = [
        { key: 'dashboard', label: 'Painel Geral', href: '/app', icon: LayoutDashboard, requiredFeature: null },
        { key: 'crm', label: 'CRM & Orçamentos', href: '/app/crm', icon: Users, requiredFeature: 'crm' },
        { key: 'inventory', label: 'Suprimentos & Kardex', href: '/app/inventory', icon: Boxes, requiredFeature: 'inventory' },
        { key: 'engineering', label: 'Engenharia (BOM)', href: '/app/production/boms', icon: Cpu, requiredFeature: 'production' },
        { key: 'production', label: 'Ordens de Produção', href: '/app/production/orders', icon: Factory, requiredFeature: 'production' },
        { key: 'work_centers', label: 'Centros de Trabalho', href: '/app/production/work-centers', icon: Building2, requiredFeature: 'production' },
        { key: 'finance', label: 'Financeiro & Fluxo', href: '/app/finance', icon: Coins, requiredFeature: 'finance' },
        { key: 'fiscal', label: 'Notas Fiscais (NFe)', href: '/app/fiscal', icon: ReceiptText, requiredFeature: 'nfe_enabled' },
    ];

    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setCommandPaletteOpen((prev) => !prev);
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    const handleLeaveImpersonation = () => {
        router.post('/admin/impersonate/leave');
    };

    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans">
            {/* Banner de Aviso de Impersonation (Suporte Landlord) */}
            {auth.is_impersonating && (
                <div className="bg-amber-500 text-slate-950 px-4 py-2 text-xs sm:text-sm font-semibold flex items-center justify-between sticky top-0 z-50 shadow-md">
                    <div className="flex items-center space-x-2">
                        <AlertTriangle className="w-4 h-4 text-slate-950 shrink-0" />
                        <span>
                            SESSÃO DE SUPORTE ATIVA: Você está atuando no tenant <strong>{auth.tenant?.name}</strong> como Landlord.
                        </span>
                    </div>
                    <button
                        onClick={handleLeaveImpersonation}
                        className="bg-slate-950 text-white text-xs px-3 py-1 rounded hover:bg-slate-900 transition"
                    >
                        Sair do Suporte
                    </button>
                </div>
            )}

            <div className="flex flex-1">
                {/* Sidebar Modular ERP */}
                <aside className="w-64 bg-slate-900 border-r border-slate-800 flex flex-col shrink-0">
                    {/* Header da Empresa */}
                    <div className="p-4 border-b border-slate-800">
                        <Link 
                            href="/select-tenant"
                            className="flex items-center justify-between p-2 rounded-lg bg-slate-800/80 hover:bg-slate-800 transition border border-slate-700/60"
                        >
                            <div className="flex items-center space-x-2.5 overflow-hidden">
                                <Building2 className="w-5 h-5 text-indigo-400 shrink-0" />
                                <div className="truncate text-left">
                                    <p className="text-xs font-bold text-white truncate">{auth.tenant?.name || 'SGI Tenant'}</p>
                                    <p className="text-[10px] text-slate-400 font-mono truncate">{auth.tenant?.subdomain}.sgi.local</p>
                                </div>
                            </div>
                            <ChevronDown className="w-4 h-4 text-slate-400 shrink-0 ml-1" />
                        </Link>
                    </div>

                    {/* Módulos do ERP com Trava de Plano */}
                    <div className="px-3 py-4 flex-1 space-y-1">
                        <div className="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Módulos Industriais
                        </div>

                        {modules.map((mod) => {
                            const Icon = mod.icon;
                            const isLocked = mod.requiredFeature ? !planFeatures[mod.requiredFeature] : false;
                            const isActive = url === mod.href || (mod.href !== '/app' && url.startsWith(mod.href));

                            if (isLocked) {
                                return (
                                    <div
                                        key={mod.key}
                                        title={`Recurso não contratado no plano ${auth.tenant?.plan?.name || ''}`}
                                        className="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-400 bg-slate-950/20 cursor-not-allowed select-none"
                                    >
                                        <div className="flex items-center space-x-3">
                                            <Icon className="w-4 h-4 shrink-0" />
                                            <span>{mod.label}</span>
                                        </div>
                                        <Lock className="w-3.5 h-3.5 text-slate-400" />
                                    </div>
                                );
                            }

                            return (
                                <Link
                                    key={mod.key}
                                    href={mod.href}
                                    className={`flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition ${
                                        isActive
                                            ? 'bg-indigo-600 text-white shadow-sm'
                                            : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                                    }`}
                                >
                                    <Icon className="w-4 h-4 shrink-0" />
                                    <span>{mod.label}</span>
                                </Link>
                            );
                        })}
                    </div>

                    {/* Atalho Chão de Fábrica Mobile & Logout */}
                    <div className="p-3 border-t border-slate-800 space-y-2">
                        <Link
                            href="/app/production/shop-floor"
                            className="flex items-center justify-center space-x-2 text-xs font-semibold py-2 px-3 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition"
                        >
                            <Smartphone className="w-4 h-4" />
                            <span>Visão Chão de Fábrica Touch</span>
                        </Link>

                        <div className="flex items-center justify-between pt-2">
                            <div className="truncate text-xs text-slate-400">
                                <span className="font-medium text-slate-200">{auth.user?.name}</span>
                            </div>
                            <button
                                onClick={handleLogout}
                                title="Sair"
                                className="text-slate-400 hover:text-rose-400 p-1 rounded hover:bg-slate-800 transition"
                            >
                                <LogOut className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </aside>

                {/* Conteúdo Principal do ERP */}
                <main className="flex-1 flex flex-col min-w-0 bg-slate-950">
                    {/* Topbar com Command Palette */}
                    <header className="h-14 border-b border-slate-800 px-6 flex items-center justify-between bg-slate-900/60 backdrop-blur shrink-0">
                        <button
                            onClick={() => setCommandPaletteOpen(true)}
                            className="flex items-center space-x-3 text-xs text-slate-400 bg-slate-800/80 hover:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-700/80 transition w-72"
                        >
                            <Search className="w-3.5 h-3.5" />
                            <span className="truncate">Busca rápida (OPs, Itens, Clientes)...</span>
                            <kbd className="ml-auto font-mono text-[10px] bg-slate-900 px-1.5 py-0.5 rounded border border-slate-700 text-slate-400">
                                Ctrl+K
                            </kbd>
                        </button>

                        <div className="flex items-center space-x-4">
                            <span className="text-xs bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-2.5 py-1 rounded-full font-medium">
                                Plano: {auth.tenant?.plan?.name || 'Standard'}
                            </span>
                        </div>
                    </header>

                    {/* Mensagens Flash */}
                    {flash.success && (
                        <div className="bg-emerald-500/10 border-b border-emerald-500/30 text-emerald-400 px-6 py-2 text-sm flex items-center space-x-2">
                            <span className="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>{flash.success}</span>
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
