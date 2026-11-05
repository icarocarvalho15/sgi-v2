import React, { ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { 
    Users, 
    Kanban, 
    FileText, 
    Printer, 
    Sliders, 
    Layers, 
    ShieldCheck, 
    CheckCircle2, 
    AlertCircle 
} from 'lucide-react';

interface Props {
    title?: string;
    children: ReactNode;
}

export default function AppLayout({ title, children }: Props) {
    const { flash, tenant } = usePage<any>().props;

    const navItems = [
        { label: 'Clientes & Fornecedores', href: '/crm/partners', icon: Users },
        { label: 'Funil de Vendas (Kanban)', href: '/crm/kanban', icon: Kanban },
        { label: 'Orçamentos Técnicos', href: '/quotes', icon: FileText },
    ];

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col md:flex-row">
            {/* Sidebar */}
            <aside className="w-full md:w-64 bg-slate-900 border-r border-slate-800 flex flex-col shrink-0">
                <div className="p-6 border-b border-slate-800 flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-white shadow-lg shadow-indigo-500/30">
                        <Printer className="w-5 h-5" />
                    </div>
                    <div>
                        <h1 className="font-bold text-base leading-tight tracking-tight text-white">SGI v2.0</h1>
                        <span className="text-xs text-indigo-400 font-medium tracking-wide">Gráficas & Brindes</span>
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
                <nav className="p-4 space-y-1 flex-1">
                    {navItems.map((item) => {
                        const Icon = item.icon;
                        const active = window.location.pathname.startsWith(item.href);
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all ${
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
                {/* Header */}
                <header className="h-16 bg-slate-900/50 backdrop-blur border-b border-slate-800/80 px-6 flex items-center justify-between sticky top-0 z-10">
                    <h2 className="text-lg font-semibold text-white tracking-tight">{title || 'Visão Geral'}</h2>
                    <div className="flex items-center gap-3">
                        <span className="text-xs text-slate-400 bg-slate-800/80 px-2.5 py-1 rounded-full border border-slate-700/60">
                            Zero-Float Mode (bcmath)
                        </span>
                    </div>
                </header>

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
