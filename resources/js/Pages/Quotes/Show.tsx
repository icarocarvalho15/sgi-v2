import React from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { ArrowLeft, CheckCircle, Printer, Calendar, Building, Clock, Layers, Sparkles } from 'lucide-react';

interface Props {
    quote: any;
}

export default function Show({ quote }: Props) {
    const formatCurrency = (val?: string) => {
        if (!val) return 'R$ 0,00';
        const num = parseFloat(val);
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(isNaN(num) ? 0 : num);
    };

    const isConverted = Boolean(quote.metadata?.converted_to_order_id);

    const handleConvert = () => {
        if (confirm('Deseja aprovar este orçamento e gerar o Pedido de Venda com Ordens de Produção (1-Clique)?')) {
            router.post(`/quotes/${quote.id}/convert`);
        }
    };

    return (
        <AppLayout title={`Orçamento ${quote.quote_number}`}>
            <div className="space-y-6 max-w-5xl">
                {/* Header Actions */}
                <div className="flex items-center justify-between">
                    <Link
                        href="/quotes"
                        className="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-slate-200 transition-colors"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Voltar para orçamentos
                    </Link>

                    <div className="flex items-center gap-3">
                        {isConverted ? (
                            <span className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                <CheckCircle className="w-4 h-4" />
                                Convertido em Pedido & O.P.
                            </span>
                        ) : (
                            <button
                                onClick={handleConvert}
                                className="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-semibold transition-all shadow-lg shadow-emerald-600/30"
                            >
                                <Sparkles className="w-4 h-4" />
                                Conversão em 1-Clique (Aprovar & Gerar OP)
                            </button>
                        )}
                    </div>
                </div>

                {/* Summary Card */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-6">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800 pb-6">
                        <div>
                            <span className="text-xs font-mono text-indigo-400 font-semibold uppercase tracking-wider block">
                                {quote.quote_number}
                            </span>
                            <h2 className="text-xl font-bold text-white tracking-tight mt-1">{quote.title}</h2>
                            <div className="flex items-center gap-4 text-xs text-slate-400 mt-2">
                                <span className="flex items-center gap-1.5">
                                    <Building className="w-3.5 h-3.5 text-slate-500" />
                                    {quote.customer?.name}
                                </span>
                                <span className="flex items-center gap-1.5">
                                    <Clock className="w-3.5 h-3.5 text-slate-500" />
                                    Prazo: {quote.production_lead_time_days} dias úteis
                                </span>
                            </div>
                        </div>

                        <div className="text-right">
                            <span className="text-xs text-slate-400 block mb-1">Valor Total Orçado</span>
                            <span className="text-3xl font-extrabold text-emerald-400 font-mono">
                                {formatCurrency(quote.total_amount)}
                            </span>
                        </div>
                    </div>

                    {/* Especificações dos Itens Técnicos */}
                    <div className="space-y-4">
                        <h4 className="text-sm font-semibold text-slate-200 flex items-center gap-2">
                            <Layers className="w-4 h-4 text-indigo-400" />
                            Itens e Engenharia de Produção
                        </h4>

                        {quote.items?.map((item: any) => (
                            <div
                                key={item.id}
                                className="p-5 bg-slate-950/70 border border-slate-800/90 rounded-xl space-y-4"
                            >
                                <div className="flex items-center justify-between border-b border-slate-800/60 pb-3">
                                    <div>
                                        <span className="font-semibold text-slate-200 text-sm block">{item.name}</span>
                                        <span className="text-xs text-indigo-400 font-medium">
                                            {item.product_type === 'commercial_print' ? 'Gráfica Offset / Digital' : 'Brinde Personalizado'}
                                        </span>
                                    </div>
                                    <div className="text-right">
                                        <span className="text-xs text-slate-400 block">Tiragem: {item.quantity} un</span>
                                        <span className="font-mono text-sm font-bold text-slate-100">
                                            {formatCurrency(item.total_price)}
                                        </span>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                    <div className="p-2.5 bg-slate-900/60 rounded-lg border border-slate-800">
                                        <span className="text-slate-500 block text-[11px]">Cores de Impressão</span>
                                        <span className="text-slate-200 font-mono font-semibold">
                                            {item.colors_front} x {item.colors_back}
                                        </span>
                                    </div>
                                    <div className="p-2.5 bg-slate-900/60 rounded-lg border border-slate-800">
                                        <span className="text-slate-500 block text-[11px]">Chapas CTP</span>
                                        <span className="text-slate-200 font-mono font-semibold">
                                            {item.ctp_plates_count} chapas
                                        </span>
                                    </div>
                                    <div className="p-2.5 bg-slate-900/60 rounded-lg border border-slate-800">
                                        <span className="text-slate-500 block text-[11px]">Substrato</span>
                                        <span className="text-slate-200 font-medium truncate block">
                                            {item.substrate_type || '—'}
                                        </span>
                                    </div>
                                    <div className="p-2.5 bg-slate-900/60 rounded-lg border border-slate-800">
                                        <span className="text-slate-500 block text-[11px]">Formato Aberto</span>
                                        <span className="text-slate-200 font-mono truncate block">
                                            {item.format_open || '—'}
                                        </span>
                                    </div>
                                </div>

                                {item.finishing_operations && item.finishing_operations.length > 0 && (
                                    <div className="text-xs">
                                        <span className="text-slate-400 block mb-1.5 font-medium">Acabamentos Especiais:</span>
                                        <div className="flex flex-wrap gap-1.5">
                                            {item.finishing_operations.map((op: string, idx: number) => (
                                                <span
                                                    key={idx}
                                                    className="px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 border border-slate-700/60 text-[11px]"
                                                >
                                                    {op}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
