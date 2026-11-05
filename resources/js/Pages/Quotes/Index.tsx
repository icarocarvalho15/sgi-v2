import React from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Plus, FileText, Calendar, Building, Clock, ChevronRight } from 'lucide-react';

interface Quote {
    id: string;
    quote_number: string;
    title: string;
    total_amount: string;
    production_lead_time_days: number;
    created_at: string;
    customer?: {
        name: string;
    };
    status?: {
        name: string;
        color: string;
    };
}

interface Props {
    quotes: {
        data: Quote[];
        links: any[];
        total: number;
    };
}

export default function Index({ quotes }: Props) {
    const formatCurrency = (val: string) => {
        const num = parseFloat(val);
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(isNaN(num) ? 0 : num);
    };

    return (
        <AppLayout title="Orçamentos Técnicos">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm text-slate-400">
                            Cálculo técnico especializado para indústria gráfica, offset, digital e brindes personalizados.
                        </p>
                    </div>

                    <Link
                        href="/quotes/create"
                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-semibold transition-all shadow-md shadow-indigo-600/20"
                    >
                        <Plus className="w-4 h-4" />
                        Novo Orçamento Técnico
                    </Link>
                </div>

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
            </div>
        </AppLayout>
    );
}
