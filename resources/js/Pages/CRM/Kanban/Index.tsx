import React, { useState } from 'react';
import AppLayout from '../../../Layouts/AppLayout';
import { DollarSign, Building, Calendar, Plus, MoreHorizontal } from 'lucide-react';

interface Stage {
    id: string;
    name: string;
    slug: string;
    color: string;
    position: number;
}

interface Deal {
    id: string;
    title: string;
    status_id: string;
    estimated_value: string;
    probability_percent: number;
    expected_close_date: string | null;
    partner?: {
        name: string;
        trade_name: string | null;
    };
}

interface Props {
    stages: Stage[];
    deals: Deal[];
}

export default function Kanban({ stages, deals: initialDeals }: Props) {
    const [deals, setDeals] = useState<Deal[]>(initialDeals);

    const formatCurrency = (val: string) => {
        const num = parseFloat(val);
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(isNaN(num) ? 0 : num);
    };

    return (
        <AppLayout title="Funil de Oportunidades (CRM)">
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm text-slate-400">
                            Acompanhamento de vendas integrado aos status customizáveis da sua gráfica.
                        </p>
                    </div>
                </div>

                {/* Kanban Horizontal Scroll Container */}
                <div className="flex gap-4 overflow-x-auto pb-6 min-h-[calc(100vh-14rem)]">
                    {stages.map((stage) => {
                        const stageDeals = deals.filter((d) => d.status_id === stage.id);
                        const totalValue = stageDeals.reduce(
                            (acc, d) => acc + (parseFloat(d.estimated_value) || 0),
                            0
                        );

                        return (
                            <div
                                key={stage.id}
                                className="w-80 shrink-0 bg-slate-900/60 border border-slate-800/80 rounded-2xl flex flex-col max-h-[80vh]"
                            >
                                {/* Column Header */}
                                <div className="p-4 border-b border-slate-800/80">
                                    <div className="flex items-center justify-between mb-1.5">
                                        <div className="flex items-center gap-2">
                                            <span
                                                className="w-2.5 h-2.5 rounded-full"
                                                style={{ backgroundColor: stage.color || '#6366F1' }}
                                            />
                                            <h4 className="font-semibold text-sm text-slate-200 truncate">
                                                {stage.name}
                                            </h4>
                                        </div>
                                        <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-800 text-slate-400">
                                            {stageDeals.length}
                                        </span>
                                    </div>
                                    <span className="text-xs text-slate-500 font-mono">
                                        Total: {new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(totalValue)}
                                    </span>
                                </div>

                                {/* Column Cards Container */}
                                <div className="p-3 space-y-3 flex-1 overflow-y-auto">
                                    {stageDeals.length === 0 ? (
                                        <div className="h-28 border border-dashed border-slate-800/70 rounded-xl flex items-center justify-center text-xs text-slate-600">
                                            Nenhuma oportunidade
                                        </div>
                                    ) : (
                                        stageDeals.map((deal) => (
                                            <div
                                                key={deal.id}
                                                className="p-4 bg-slate-950/80 hover:bg-slate-950 border border-slate-800/90 rounded-xl shadow-md transition-all hover:border-slate-700/80 space-y-2.5"
                                            >
                                                <div className="flex items-start justify-between gap-2">
                                                    <span className="font-medium text-xs text-slate-200 line-clamp-2">
                                                        {deal.title}
                                                    </span>
                                                    <span className="text-[10px] font-semibold text-indigo-400 px-1.5 py-0.5 bg-indigo-500/10 rounded">
                                                        {deal.probability_percent}%
                                                    </span>
                                                </div>

                                                {deal.partner && (
                                                    <div className="flex items-center gap-1.5 text-xs text-slate-400">
                                                        <Building className="w-3.5 h-3.5 text-slate-500 shrink-0" />
                                                        <span className="truncate">{deal.partner.name}</span>
                                                    </div>
                                                )}

                                                <div className="flex items-center justify-between pt-1 border-t border-slate-800/60 text-xs">
                                                    <span className="font-semibold text-emerald-400 font-mono">
                                                        {formatCurrency(deal.estimated_value)}
                                                    </span>
                                                    {deal.expected_close_date && (
                                                        <div className="flex items-center gap-1 text-[11px] text-slate-500">
                                                            <Calendar className="w-3 h-3" />
                                                            <span>{deal.expected_close_date}</span>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
