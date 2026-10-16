import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import ShopFloorMobileLayout from '@/Layouts/ShopFloorMobileLayout';
import { PaginatedResponse, Item } from '@/types';
import { 
    Play, 
    Pause, 
    CheckCircle, 
    Barcode, 
    Search, 
    Clock, 
    Layers, 
    Filter,
    Activity,
    AlertCircle
} from 'lucide-react';

interface WorkCenter {
    id: string;
    code: string;
    name: string;
}

interface ProductionOrder {
    id: string;
    order_number: string;
    item?: Item;
    planned_quantity: string;
    produced_quantity: string;
}

interface Operation {
    id: string;
    sequence: number;
    status: 'PENDING' | 'IN_PROGRESS' | 'PAUSED' | 'COMPLETED';
    executed_time_seconds: number;
    started_at: string | null;
    production_order: ProductionOrder;
    work_center: WorkCenter;
    operator?: { name: string } | null;
}

interface Props {
    operations: PaginatedResponse<Operation>;
    workCenters: WorkCenter[];
    filters: {
        work_center_id: string;
        status: string;
        barcode: string;
    };
}

export default function ShopFloorIndex({
    operations,
    workCenters,
    filters,
}: Props) {
    const [barcodeInput, setBarcodeInput] = useState(filters.barcode || '');
    const [loadingOpId, setLoadingOpId] = useState<string | null>(null);

    const handleBarcodeSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/app/production/shop-floor', {
            ...filters,
            barcode: barcodeInput,
        }, { preserveState: true });
    };

    const handleFilterWorkCenter = (wcId: string) => {
        const nextId = filters.work_center_id === wcId ? '' : wcId;
        router.get('/app/production/shop-floor', {
            ...filters,
            work_center_id: nextId,
        }, { preserveState: true });
    };

    const handleStart = (opId: string) => {
        setLoadingOpId(opId);
        router.post(`/app/production/operations/${opId}/start`, {}, {
            preserveScroll: true,
            onFinish: () => setLoadingOpId(null),
        });
    };

    const handlePause = (opId: string) => {
        setLoadingOpId(opId);
        router.post(`/app/production/operations/${opId}/pause`, {}, {
            preserveScroll: true,
            onFinish: () => setLoadingOpId(null),
        });
    };

    const handleComplete = (opId: string) => {
        setLoadingOpId(opId);
        router.post(`/app/production/operations/${opId}/complete`, {}, {
            preserveScroll: true,
            onFinish: () => setLoadingOpId(null),
        });
    };

    const formatTimer = (sec: number) => {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return `${m}m ${s < 10 ? '0' : ''}${s}s`;
    };

    return (
        <ShopFloorMobileLayout title="Chão de Fábrica Touch">
            <Head title="Terminal Chão de Fábrica - SGI v2.0" />

            <div className="space-y-4">
                {/* Leitor de Código de Barras / QR Code & Busca Rápida */}
                <form onSubmit={handleBarcodeSearch} className="flex items-center space-x-2">
                    <div className="relative flex-1">
                        <Barcode className="w-5 h-5 text-emerald-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            type="text"
                            placeholder="Ler Código de Barras / SKU / OP..."
                            value={barcodeInput}
                            onChange={(e) => setBarcodeInput(e.target.value)}
                            className="w-full bg-slate-900 border-2 border-slate-700 focus:border-emerald-500 rounded-2xl pl-11 pr-4 py-3 text-base text-white font-mono placeholder-slate-500 shadow-inner"
                        />
                    </div>
                    <button
                        type="submit"
                        className="bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white font-bold p-3 rounded-2xl shadow-lg transition"
                    >
                        <Search className="w-5 h-5" />
                    </button>
                </form>

                {/* Filtro por Centro de Trabalho (Touch Pills) */}
                <div className="flex items-center space-x-2 overflow-x-auto pb-1 pt-1 no-scrollbar">
                    <button
                        onClick={() => handleFilterWorkCenter('')}
                        className={`px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap active:scale-95 transition ${
                            !filters.work_center_id
                                ? 'bg-indigo-600 text-white shadow-md'
                                : 'bg-slate-900 text-slate-400 border border-slate-800'
                        }`}
                    >
                        Todos os Centros
                    </button>

                    {workCenters.map((wc) => {
                        const isSelected = filters.work_center_id === wc.id;
                        return (
                            <button
                                key={wc.id}
                                onClick={() => handleFilterWorkCenter(wc.id)}
                                className={`px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap active:scale-95 transition ${
                                    isSelected
                                        ? 'bg-indigo-600 text-white shadow-md'
                                        : 'bg-slate-900 text-slate-400 border border-slate-800 hover:border-slate-700'
                                }`}
                            >
                                [{wc.code}] {wc.name}
                            </button>
                        );
                    })}
                </div>

                {/* Lista de Operações do Chão de Fábrica */}
                <div className="space-y-3">
                    {operations.data.length === 0 ? (
                        <div className="bg-slate-900 border border-slate-800 rounded-2xl p-8 text-center text-slate-400">
                            <Activity className="w-8 h-8 mx-auto text-slate-600 mb-2 animate-bounce" />
                            <p className="font-bold text-slate-300">Nenhuma operação pendente no momento</p>
                            <p className="text-xs text-slate-500 mt-1">Libere novas Ordens de Produção no módulo Desktop</p>
                        </div>
                    ) : (
                        operations.data.map((op) => {
                            const isRunning = op.status === 'IN_PROGRESS';
                            const isPaused = op.status === 'PAUSED';
                            const isCompleted = op.status === 'COMPLETED';

                            return (
                                <div
                                    key={op.id}
                                    className={`bg-slate-900 border-2 rounded-2xl p-4 sm:p-5 transition shadow-lg ${
                                        isRunning
                                            ? 'border-emerald-500 shadow-emerald-950/40'
                                            : isPaused
                                            ? 'border-amber-500/60'
                                            : 'border-slate-800'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <div>
                                            <div className="flex items-center space-x-2">
                                                <span className="font-mono text-xs font-bold bg-slate-800 text-indigo-400 px-2 py-0.5 rounded">
                                                    Seq {op.sequence}
                                                </span>
                                                <span className="font-mono font-bold text-white text-base">
                                                    {op.production_order?.order_number}
                                                </span>
                                            </div>

                                            <h3 className="text-base font-bold text-slate-100 mt-1">
                                                {op.production_order?.item?.name}
                                            </h3>
                                            <p className="text-xs font-mono text-slate-400">
                                                SKU: {op.production_order?.item?.sku}
                                            </p>
                                        </div>

                                        <span className={`text-[11px] font-bold px-2.5 py-1 rounded-full uppercase ${
                                            isRunning ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 animate-pulse' :
                                            isPaused ? 'bg-amber-500/20 text-amber-400 border border-amber-500/40' :
                                            isCompleted ? 'bg-slate-800 text-slate-400' :
                                            'bg-sky-500/10 text-sky-400 border border-sky-500/30'
                                        }`}>
                                            {op.status}
                                        </span>
                                    </div>

                                    {/* Info Centro de Trabalho e Timer */}
                                    <div className="mt-3 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                                        <div className="flex items-center space-x-1.5">
                                            <span className="font-medium text-slate-300">
                                                {op.work_center?.name}
                                            </span>
                                        </div>

                                        <div className="flex items-center space-x-1.5 font-mono text-slate-200">
                                            <Clock className="w-3.5 h-3.5 text-indigo-400" />
                                            <span>{formatTimer(op.executed_time_seconds)}</span>
                                        </div>
                                    </div>

                                    {/* Ações Rápidas de Toque */}
                                    {!isCompleted && (
                                        <div className="mt-4 grid grid-cols-2 gap-2">
                                            {!isRunning ? (
                                                <button
                                                    onClick={() => handleStart(op.id)}
                                                    disabled={loadingOpId === op.id}
                                                    className="col-span-2 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white font-bold py-3 rounded-xl flex items-center justify-center space-x-2 text-sm shadow-lg shadow-emerald-950/50 transition"
                                                >
                                                    <Play className="w-4 h-4 fill-white" />
                                                    <span>INICIAR OPERAÇÃO</span>
                                                </button>
                                            ) : (
                                                <>
                                                    <button
                                                        onClick={() => handlePause(op.id)}
                                                        disabled={loadingOpId === op.id}
                                                        className="bg-amber-600 hover:bg-amber-500 active:scale-95 text-white font-bold py-3 rounded-xl flex items-center justify-center space-x-2 text-sm shadow-lg transition"
                                                    >
                                                        <Pause className="w-4 h-4 fill-white" />
                                                        <span>PAUSAR</span>
                                                    </button>
                                                    <button
                                                        onClick={() => handleComplete(op.id)}
                                                        disabled={loadingOpId === op.id}
                                                        className="bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold py-3 rounded-xl flex items-center justify-center space-x-2 text-sm shadow-lg shadow-indigo-950/50 transition"
                                                    >
                                                        <CheckCircle className="w-4 h-4" />
                                                        <span>CONCLUIR</span>
                                                    </button>
                                                </>
                                            )}
                                        </div>
                                    )}
                                </div>
                            );
                        })
                    )}
                </div>
            </div>
        </ShopFloorMobileLayout>
    );
}
