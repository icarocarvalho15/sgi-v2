import React, { useState } from 'react';
import { Link, router, Head } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { 
    Plus, 
    FileText, 
    Calendar, 
    Building, 
    Clock, 
    ChevronRight, 
    Kanban as KanbanIcon, 
    List, 
    ArrowRight, 
    CheckCircle2, 
    DollarSign,
    Layers,
    Filter
} from 'lucide-react';

interface Quote {
    id: string;
    quote_number: string;
    title: string;
    status_id: string;
    total_amount: string;
    estimated_cost?: string;
    production_lead_time_days: number;
    created_at: string;
    customer?: {
        id: string;
        name: string;
        trade_name?: string | null;
    };
    status?: {
        id: string;
        name: string;
        slug: string;
        color: string;
        is_default?: boolean;
        is_final?: boolean;
    };
    items?: Array<{
        id: string;
        name: string;
        quantity: number;
        total_price: string;
    }>;
}

interface Status {
    id: string;
    name: string;
    slug: string;
    color: string;
    position: number;
    is_default: boolean;
    is_final: boolean;
}

interface Props {
    quotes: {
        data: Quote[];
        links: any[];
        total: number;
    };
    kanbanQuotes?: Quote[];
    statuses?: Status[];
    initialView?: 'table' | 'kanban';
}

export default function Index({ 
    quotes, 
    kanbanQuotes: initialKanbanQuotes = [], 
    statuses = [], 
    initialView = 'table' 
}: Props) {
    const [viewMode, setViewMode] = useState<'table' | 'kanban'>(initialView);
    const [kanbanQuotes, setKanbanQuotes] = useState<Quote[]>(
        initialKanbanQuotes.length > 0 ? initialKanbanQuotes : quotes.data
    );
    const [draggingQuoteId, setDraggingQuoteId] = useState<string | null>(null);
    const [dragOverStatusId, setDragOverStatusId] = useState<string | null>(null);

    // Formatação de Moeda
    const formatCurrency = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(isNaN(num) ? 0 : num);
    };

    // Alternar modo de visualização e sincronizar URL
    const switchView = (mode: 'table' | 'kanban') => {
        setViewMode(mode);
        const url = new URL(window.location.href);
        url.searchParams.set('view', mode);
        window.history.replaceState({}, '', url.toString());
    };

    // Atualização de Status (Drag & Drop ou Select)
    const handleStatusChange = (quoteId: string, newStatusId: string) => {
        const targetStatus = statuses.find((s) => s.id === newStatusId);
        if (!targetStatus) return;

        // Atualização Otimista no State Local
        setKanbanQuotes((prev) =>
            prev.map((q) => {
                if (q.id === quoteId) {
                    return {
                        ...q,
                        status_id: newStatusId,
                        status: {
                            id: targetStatus.id,
                            name: targetStatus.name,
                            slug: targetStatus.slug,
                            color: targetStatus.color,
                            is_default: targetStatus.is_default,
                            is_final: targetStatus.is_final,
                        },
                    };
                }
                return q;
            })
        );

        // Chamada ao Backend
        router.post(
            `/quotes/${quoteId}/status`,
            { status_id: newStatusId },
            {
                preserveScroll: true,
                preserveState: true,
                onError: () => {
                    // Reverte se falhar
                    setKanbanQuotes(initialKanbanQuotes);
                },
            }
        );
    };

    // Handlers de Drag & Drop HTML5
    const handleDragStart = (e: React.DragEvent, quoteId: string) => {
        e.dataTransfer.setData('text/plain', quoteId);
        setDraggingQuoteId(quoteId);
    };

    const handleDragOver = (e: React.DragEvent, statusId: string) => {
        e.preventDefault();
        if (dragOverStatusId !== statusId) {
            setDragOverStatusId(statusId);
        }
    };

    const handleDragLeave = (e: React.DragEvent, statusId: string) => {
        if (dragOverStatusId === statusId) {
            setDragOverStatusId(null);
        }
    };

    const handleDrop = (e: React.DragEvent, statusId: string) => {
        e.preventDefault();
        setDragOverStatusId(null);
        const quoteId = e.dataTransfer.getData('text/plain') || draggingQuoteId;
        setDraggingQuoteId(null);

        if (quoteId) {
            handleStatusChange(quoteId, statusId);
        }
    };

    // Estatísticas Globais
    const totalPipelineValue = kanbanQuotes.reduce(
        (sum, q) => sum + (parseFloat(q.total_amount) || 0),
        0
    );

    return (
        <AppLayout title="Orçamentos Técnicos & Funil de Vendas">
            <Head title="Orçamentos Técnicos & Funil de Vendas - SGI v2.0" />

            <div className="space-y-6">
                {/* Header Superior com Alternador de Visualização */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/60 p-5 rounded-2xl border border-slate-800/80 shadow-lg">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center gap-1">
                                <FileText className="w-3 h-3" /> Módulo Comercial Gráfico
                            </span>
                            <span className="text-xs text-slate-500">•</span>
                            <span className="text-xs text-slate-400">
                                {kanbanQuotes.length} {kanbanQuotes.length === 1 ? 'orçamento emitido' : 'orçamentos emitidos'}
                            </span>
                        </div>
                        <h1 className="text-xl font-bold text-white tracking-tight">
                            Orçamentos Técnicos & Funil de Vendas
                        </h1>
                        <p className="text-xs text-slate-400 mt-0.5">
                            Cálculo técnico especializado para indústria gráfica, offset, digital e brindes com funil comercial integrado.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        {/* Seletor Lista / Funil */}
                        <div className="bg-slate-950/80 p-1 rounded-xl border border-slate-800 flex items-center">
                            <button
                                onClick={() => switchView('table')}
                                className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                    viewMode === 'table'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-slate-400 hover:text-slate-200'
                                }`}
                            >
                                <List className="w-3.5 h-3.5" />
                                Lista
                            </button>
                            <button
                                onClick={() => switchView('kanban')}
                                className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                    viewMode === 'kanban'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-slate-400 hover:text-slate-200'
                                }`}
                            >
                                <KanbanIcon className="w-3.5 h-3.5" />
                                Funil (Kanban)
                            </button>
                        </div>

                        {/* Botão Novo Orçamento */}
                        <Link
                            href="/quotes/create"
                            className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition-all shadow-md shadow-indigo-600/20"
                        >
                            <Plus className="w-4 h-4" />
                            Novo Orçamento Técnico
                        </Link>
                    </div>
                </div>

                {/* VISUALIZAÇÃO 1: FUNIL DE VENDAS (KANBAN) */}
                {viewMode === 'kanban' && (
                    <div className="space-y-3">
                        {/* Pipeline Stats */}
                        <div className="flex items-center justify-between px-2">
                            <span className="text-xs font-medium text-slate-400">
                                Arraste os cards entre as colunas para atualizar o estágio de negociação do orçamento.
                            </span>
                            <div className="flex items-center gap-2 text-xs font-medium text-slate-300">
                                <span className="text-slate-500">Valor Total no Funil:</span>
                                <span className="font-mono font-bold text-emerald-400">
                                    {formatCurrency(totalPipelineValue)}
                                </span>
                            </div>
                        </div>

                        {/* Colunas do Kanban */}
                        <div className="flex gap-4 overflow-x-auto pb-6 min-h-[calc(100vh-17rem)]">
                            {statuses.length === 0 ? (
                                <div className="w-full bg-slate-900/50 border border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                                    <p className="text-sm">Nenhum status configurado para o módulo de Orçamentos.</p>
                                    <Link
                                        href="/settings/statuses"
                                        className="text-xs text-indigo-400 hover:underline mt-2 inline-block"
                                    >
                                        Configurar Status no Painel
                                    </Link>
                                </div>
                            ) : (
                                statuses.map((status) => {
                                    const columnQuotes = kanbanQuotes.filter(
                                        (q) => q.status_id === status.id || q.status?.id === status.id
                                    );
                                    const columnTotal = columnQuotes.reduce(
                                        (acc, q) => acc + (parseFloat(q.total_amount) || 0),
                                        0
                                    );
                                    const isDragOver = dragOverStatusId === status.id;

                                    return (
                                        <div
                                            key={status.id}
                                            onDragOver={(e) => handleDragOver(e, status.id)}
                                            onDragLeave={(e) => handleDragLeave(e, status.id)}
                                            onDrop={(e) => handleDrop(e, status.id)}
                                            className={`w-80 shrink-0 bg-slate-900/60 border rounded-2xl flex flex-col max-h-[75vh] transition-colors ${
                                                isDragOver
                                                    ? 'border-indigo-500/80 bg-slate-900/90 ring-2 ring-indigo-500/20'
                                                    : 'border-slate-800/80'
                                            }`}
                                        >
                                            {/* Cabeçalho da Coluna */}
                                            <div className="p-4 border-b border-slate-800/80 bg-slate-900/40 rounded-t-2xl">
                                                <div className="flex items-center justify-between mb-1.5">
                                                    <div className="flex items-center gap-2 min-w-0">
                                                        <span
                                                            className="w-2.5 h-2.5 rounded-full shrink-0"
                                                            style={{ backgroundColor: status.color || '#6366F1' }}
                                                        />
                                                        <h4 className="font-semibold text-sm text-slate-200 truncate">
                                                            {status.name}
                                                        </h4>
                                                    </div>
                                                    <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 shrink-0">
                                                        {columnQuotes.length}
                                                    </span>
                                                </div>
                                                <div className="flex items-center justify-between text-xs text-slate-500 font-mono">
                                                    <span>Total:</span>
                                                    <span className="font-semibold text-slate-300">
                                                        {formatCurrency(columnTotal)}
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Container de Cards */}
                                            <div className="p-3 space-y-3 flex-1 overflow-y-auto">
                                                {columnQuotes.length === 0 ? (
                                                    <div className="h-28 border border-dashed border-slate-800/70 rounded-xl flex items-center justify-center text-xs text-slate-600 text-center px-4">
                                                        Nenhum orçamento nesta etapa
                                                    </div>
                                                ) : (
                                                    columnQuotes.map((quote) => (
                                                        <div
                                                            key={quote.id}
                                                            draggable
                                                            onDragStart={(e) => handleDragStart(e, quote.id)}
                                                            className={`p-4 bg-slate-950/80 hover:bg-slate-950 border border-slate-800/90 rounded-xl shadow-md transition-all hover:border-slate-700/80 space-y-3 cursor-grab active:cursor-grabbing ${
                                                                draggingQuoteId === quote.id
                                                                    ? 'opacity-40 border-indigo-500 border-dashed'
                                                                    : ''
                                                            }`}
                                                        >
                                                            {/* Topo do Card */}
                                                            <div className="flex items-start justify-between gap-2">
                                                                <span className="font-mono text-[11px] font-bold text-indigo-400 bg-indigo-500/10 px-1.5 py-0.5 rounded">
                                                                    {quote.quote_number}
                                                                </span>
                                                                <div className="flex items-center gap-1 text-[11px] text-slate-400">
                                                                    <Clock className="w-3 h-3 text-slate-500" />
                                                                    <span>{quote.production_lead_time_days}d</span>
                                                                </div>
                                                            </div>

                                                            {/* Título e Cliente */}
                                                            <div>
                                                                <h5 className="font-medium text-xs text-slate-200 line-clamp-2 leading-relaxed">
                                                                    {quote.title}
                                                                </h5>
                                                                <div className="flex items-center gap-1.5 text-xs text-slate-400 mt-1">
                                                                    <Building className="w-3.5 h-3.5 text-slate-500 shrink-0" />
                                                                    <span className="truncate">
                                                                        {quote.customer?.name || 'Cliente não associado'}
                                                                    </span>
                                                                </div>
                                                            </div>

                                                            {/* Rodapé do Card: Valor e Ações */}
                                                            <div className="pt-2 border-t border-slate-800/60 flex items-center justify-between text-xs">
                                                                <span className="font-semibold text-emerald-400 font-mono text-sm">
                                                                    {formatCurrency(quote.total_amount)}
                                                                </span>

                                                                <Link
                                                                    href={`/quotes/${quote.id}`}
                                                                    className="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-400 hover:text-indigo-300 transition-colors"
                                                                >
                                                                    Detalhes
                                                                    <ChevronRight className="w-3 h-3" />
                                                                </Link>
                                                            </div>

                                                            {/* Seletor Rápido de Status (Para quem prefere clique) */}
                                                            <div className="pt-1">
                                                                <select
                                                                    value={quote.status_id || quote.status?.id}
                                                                    onChange={(e) => handleStatusChange(quote.id, e.target.value)}
                                                                    className="w-full text-[10px] py-1 px-2 bg-slate-900 border border-slate-800 text-slate-400 rounded-lg focus:ring-1 focus:ring-indigo-500"
                                                                >
                                                                    {statuses.map((s) => (
                                                                        <option key={s.id} value={s.id}>
                                                                            Mover para: {s.name}
                                                                        </option>
                                                                    ))}
                                                                </select>
                                                            </div>
                                                        </div>
                                                    ))
                                                )}
                                            </div>
                                        </div>
                                    );
                                })
                            )}
                        </div>
                    </div>
                )}

                {/* VISUALIZAÇÃO 2: TABELA (LISTA TRADICIONAL) */}
                {viewMode === 'table' && (
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm text-slate-300">
                                <thead className="bg-slate-800/40 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                                    <tr>
                                        <th className="px-6 py-4">Número</th>
                                        <th className="px-6 py-4">Título / Descrição</th>
                                        <th className="px-6 py-4">Cliente</th>
                                        <th className="px-6 py-4">Status</th>
                                        <th className="px-6 py-4">Prazo</th>
                                        <th className="px-6 py-4">Valor Total</th>
                                        <th className="px-6 py-4 text-right">Ação</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-800/60">
                                    {quotes.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="px-6 py-12 text-center text-slate-500">
                                                Nenhum orçamento emitido até o momento.
                                            </td>
                                        </tr>
                                    ) : (
                                        quotes.data.map((quote) => (
                                            <tr key={quote.id} className="hover:bg-slate-800/30 transition-colors">
                                                <td className="px-6 py-4 font-mono text-xs text-indigo-400 font-semibold">
                                                    {quote.quote_number}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="font-medium text-slate-200 block">{quote.title}</span>
                                                </td>
                                                <td className="px-6 py-4 text-xs text-slate-300">
                                                    <div className="flex items-center gap-1.5">
                                                        <Building className="w-3.5 h-3.5 text-slate-500" />
                                                        <span>{quote.customer?.name || 'Cliente não associado'}</span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {quote.status && (
                                                        <span
                                                            className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border"
                                                            style={{
                                                                color: quote.status.color,
                                                                borderColor: `${quote.status.color}40`,
                                                                backgroundColor: `${quote.status.color}15`,
                                                            }}
                                                        >
                                                            <span
                                                                className="w-1.5 h-1.5 rounded-full"
                                                                style={{ backgroundColor: quote.status.color }}
                                                            />
                                                            {quote.status.name}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 text-xs text-slate-400">
                                                    <div className="flex items-center gap-1">
                                                        <Clock className="w-3.5 h-3.5 text-slate-500" />
                                                        <span>{quote.production_lead_time_days} dias úteis</span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 font-mono text-sm font-semibold text-emerald-400">
                                                    {formatCurrency(quote.total_amount)}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <Link
                                                        href={`/quotes/${quote.id}`}
                                                        className="inline-flex items-center gap-1 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors"
                                                    >
                                                        Visualizar
                                                        <ChevronRight className="w-3.5 h-3.5" />
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
