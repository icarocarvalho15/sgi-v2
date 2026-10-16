import React, { ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { 
    Factory, 
    ArrowLeft, 
    User as UserIcon, 
    Radio
} from 'lucide-react';

interface Props {
    children: ReactNode;
    title?: string;
}

export default function ShopFloorMobileLayout({ children, title }: Props) {
    const { props } = usePage<PageProps>();
    const { auth, flash } = props;

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans select-none">
            {/* Topbar Industrial Touch */}
            <header className="h-16 bg-slate-900 border-b border-slate-800 px-4 flex items-center justify-between sticky top-0 z-40 shadow-lg">
                <div className="flex items-center space-x-3">
                    <Link
                        href="/app/production/orders"
                        className="p-2.5 rounded-xl bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 active:scale-95 transition"
                        title="Voltar ao ERP Desktop"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <div className="flex items-center space-x-2">
                            <Factory className="w-4 h-4 text-emerald-400" />
                            <h1 className="text-sm font-bold text-white tracking-wide uppercase">
                                Chão de Fábrica Touch
                            </h1>
                            <span className="flex items-center space-x-1 text-[10px] bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded-full border border-emerald-500/30">
                                <Radio className="w-2.5 h-2.5 animate-pulse text-emerald-400" />
                                <span>ONLINE</span>
                            </span>
                        </div>
                        <p className="text-[11px] text-slate-400 truncate max-w-[200px] sm:max-w-xs">
                            {auth.tenant?.name || 'SGI Manufatura'}
                        </p>
                    </div>
                </div>

                <div className="flex items-center space-x-2">
                    <div className="hidden sm:flex items-center space-x-2 bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-700">
                        <UserIcon className="w-4 h-4 text-slate-400" />
                        <span className="text-xs font-medium text-slate-200">{auth.user?.name}</span>
                    </div>
                </div>
            </header>

            {/* Mensagens Flash */}
            {flash.success && (
                <div className="bg-emerald-600 text-white px-4 py-2 text-sm font-semibold flex items-center justify-between shadow-md">
                    <span>{flash.success}</span>
                </div>
            )}
            {flash.error && (
                <div className="bg-rose-600 text-white px-4 py-2 text-sm font-semibold flex items-center justify-between shadow-md">
                    <span>{flash.error}</span>
                </div>
            )}

            {/* Conteúdo Principal Touch */}
            <main className="flex-1 p-4 sm:p-6 max-w-5xl w-full mx-auto pb-16">
                {children}
            </main>
        </div>
    );
}
