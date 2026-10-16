import React, { ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { 
    QrCode, 
    PlayCircle, 
    AlertOctagon, 
    ListOrdered, 
    ArrowLeft,
    CheckCircle2
} from 'lucide-react';

interface Props {
    children: ReactNode;
    title?: string;
    showBackToDesktop?: boolean;
}

export default function ShopFloorMobileLayout({ children, title = 'Terminal de Chão de Fábrica', showBackToDesktop = true }: Props) {
    const { auth } = usePage<PageProps>().props;

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans select-none pb-24">
            {/* Topbar Industrial Alto Contraste */}
            <header className="h-16 bg-slate-900 border-b border-slate-800 px-4 flex items-center justify-between sticky top-0 z-40 shadow-md">
                <div className="flex items-center space-x-3 overflow-hidden">
                    <div className="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center font-black text-sm shrink-0">
                        OP
                    </div>
                    <div className="truncate">
                        <h1 className="text-sm font-bold text-white tracking-wide truncate">{title}</h1>
                        <p className="text-[11px] text-slate-400 truncate">{auth.tenant?.name || 'SGI Fábrica'}</p>
                    </div>
                </div>

                {showBackToDesktop && (
                    <Link
                        href="/app"
                        className="text-xs bg-slate-800 border border-slate-700 text-slate-300 px-3 py-2 rounded-lg font-medium hover:bg-slate-700 transition shrink-0 flex items-center space-x-1"
                    >
                        <ArrowLeft className="w-3.5 h-3.5" />
                        <span>Sair</span>
                    </Link>
                )}
            </header>

            {/* Área de Trabalho Touch */}
            <main className="p-4 flex-1 flex flex-col max-w-md mx-auto w-full">
                {children}
            </main>

            {/* Bottom Navigation Bar Fixa com Botões de Altura Mínima >= 56px (h-14) */}
            <nav className="fixed bottom-0 left-0 right-0 h-20 bg-slate-900/95 backdrop-blur border-t border-slate-800 px-3 flex items-center justify-around z-50">
                <button
                    type="button"
                    className="flex-1 flex flex-col items-center justify-center h-14 rounded-xl text-amber-400 hover:bg-slate-800/80 active:scale-95 transition"
                >
                    <ListOrdered className="w-6 h-6 mb-1" />
                    <span className="text-[11px] font-bold tracking-tight">Minhas OPs</span>
                </button>

                <button
                    type="button"
                    className="flex-1 flex flex-col items-center justify-center h-14 rounded-xl text-indigo-400 hover:bg-slate-800/80 active:scale-95 transition"
                >
                    <QrCode className="w-6 h-6 mb-1" />
                    <span className="text-[11px] font-bold tracking-tight">Ler QR Code</span>
                </button>

                <button
                    type="button"
                    className="flex-1 flex flex-col items-center justify-center h-14 rounded-xl text-emerald-400 hover:bg-slate-800/80 active:scale-95 transition"
                >
                    <PlayCircle className="w-6 h-6 mb-1" />
                    <span className="text-[11px] font-bold tracking-tight">Apontar</span>
                </button>

                <button
                    type="button"
                    className="flex-1 flex flex-col items-center justify-center h-14 rounded-xl text-rose-400 hover:bg-slate-800/80 active:scale-95 transition"
                >
                    <AlertOctagon className="w-6 h-6 mb-1" />
                    <span className="text-[11px] font-bold tracking-tight">Parada</span>
                </button>
            </nav>
        </div>
    );
}
