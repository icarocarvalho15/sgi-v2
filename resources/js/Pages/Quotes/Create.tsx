import React, { useState, useEffect } from 'react';
import { useForm, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { ArrowLeft, Calculator, Layers, Printer, Sparkles, ShieldAlert, Check } from 'lucide-react';

interface Customer {
    id: string;
    name: string;
    document_number: string;
}

interface Status {
    id: string;
    name: string;
    color: string;
    is_default: boolean;
}

interface Props {
    customers: Customer[];
    statuses: Status[];
}

export default function Create({ customers, statuses }: Props) {
    const defaultStatus = statuses.find((s) => s.is_default) || statuses[0];

    const { data, setData, post, processing, errors } = useForm({
        customer_id: customers[0]?.id || '',
        status_id: defaultStatus?.id || '',
        title: '',
        production_lead_time_days: 5,
        valid_until: new Date(Date.now() + 15 * 86400000).toISOString().split('T')[0],
        notes: '',
        items: [
            {
                product_type: 'commercial_print',
                name: 'Folder Institucional 4x4',
                quantity: 1000,
                format_open: '297x420 mm (A3)',
                format_closed: '210x297 mm (A4)',
                substrate_type: 'Papel Couché Fosco 150g',
                colors_front: 4,
                colors_back: 4,
                customization_process: '',
                finishing_operations: ['Refile Guilhotina', 'Dobra Central'],
                ctp_plate_cost: '35.0000',
                substrate_unit_cost: '0.4500',
                machine_run_cost_per_thousand: '40.0000',
                customization_unit_cost: '0.0000',
                finishing_flat_cost: '60.0000',
                markup_multiplier: '1.6000',
            },
        ],
    });

    const [simulation, setSimulation] = useState<any>(null);
    const [simulating, setSimulating] = useState(false);

    const activeItem = data.items[0];

    // Real-time calculation effect
    useEffect(() => {
        const fetchCalc = async () => {
            setSimulating(true);
            try {
                const res = await fetch('/quotes/calculate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                    },
                    body: JSON.stringify(activeItem),
                });
                if (res.ok) {
                    const result = await res.json();
                    setSimulation(result);
                }
            } catch (err) {
                console.error(err);
            } finally {
                setSimulating(false);
            }
        };

        const timer = setTimeout(fetchCalc, 250);
        return () => clearTimeout(timer);
    }, [
        activeItem.quantity,
        activeItem.colors_front,
        activeItem.colors_back,
        activeItem.substrate_unit_cost,
        activeItem.markup_multiplier,
        activeItem.finishing_flat_cost,
        activeItem.product_type,
    ]);

    const handleItemChange = (field: string, value: any) => {
        const updatedItems = [...data.items];
        updatedItems[0] = { ...updatedItems[0], [field]: value };
        setData('items', updatedItems);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/quotes');
    };

    const formatCurrency = (val?: string) => {
        if (!val) return 'R$ 0,00';
        const num = parseFloat(val);
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(isNaN(num) ? 0 : num);
    };

    return (
        <AppLayout title="Novo Orçamento Técnico (Simulador Gráfico)">
            <form onSubmit={handleSubmit} className="space-y-6">
                <div className="flex items-center justify-between">
                    <Link
                        href="/quotes"
                        className="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-slate-200 transition-colors"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Voltar para lista de orçamentos
                    </Link>

                    <button
                        type="submit"
                        disabled={processing}
                        className="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white rounded-xl text-sm font-semibold transition-all shadow-md shadow-indigo-600/20"
                    >
                        {processing ? 'Emitindo Orçamento...' : 'Gerar Orçamento Técnico'}
                    </button>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Parâmetros do Orçamento & Especificação Industrial */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Cabeçalho do Orçamento */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-4">
                            <h3 className="text-base font-semibold text-slate-100 flex items-center gap-2 border-b border-slate-800 pb-3">
                                <Printer className="w-4 h-4 text-indigo-400" />
                                Dados do Cliente & Validade
                            </h3>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Cliente *</label>
                                    <select
                                        value={data.customer_id}
                                        onChange={(e) => setData('customer_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="">Selecione o Cliente</option>
                                        {customers.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name} ({c.document_number})
                                            </option>
                                        ))}
                                    </select>
                                    {errors.customer_id && (
                                        <span className="text-xs text-rose-400 mt-1 block">{errors.customer_id}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Status Inicial *</label>
                                    <select
                                        value={data.status_id}
                                        onChange={(e) => setData('status_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    >
                                        {statuses.map((s) => (
                                            <option key={s.id} value={s.id}>
                                                {s.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="md:col-span-2">
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Título do Trabalho Gráfico / Brinde *
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Ex: 1.000 Catálogos A4 Couché 150g ou 500 Canecas Laser"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                    {errors.title && <span className="text-xs text-rose-400 mt-1 block">{errors.title}</span>}
                                </div>
                            </div>
                        </div>

                        {/* Engenharia do Produto Gráfico */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-4">
                            <h3 className="text-base font-semibold text-slate-100 flex items-center gap-2 border-b border-slate-800 pb-3">
                                <Layers className="w-4 h-4 text-indigo-400" />
                                Composição Técnica & Tiragem
                            </h3>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Nicho / Tipo</label>
                                    <select
                                        value={activeItem.product_type}
                                        onChange={(e) => handleItemChange('product_type', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200"
                                    >
                                        <option value="commercial_print">Gráfica (Offset / Digital)</option>
                                        <option value="custom_gift">Brinde Personalizado</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Tiragem (Quantidade) *</label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={activeItem.quantity}
                                        onChange={(e) => handleItemChange('quantity', parseInt(e.target.value) || 1)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 font-mono"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Substrato / Material</label>
                                    <input
                                        type="text"
                                        value={activeItem.substrate_type}
                                        onChange={(e) => handleItemChange('substrate_type', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Cores Frente (ex: 4 = CMYK)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        max="12"
                                        value={activeItem.colors_front}
                                        onChange={(e) => handleItemChange('colors_front', parseInt(e.target.value) || 0)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 font-mono"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Cores Verso</label>
                                    <input
                                        type="number"
                                        min="0"
                                        max="12"
                                        value={activeItem.colors_back}
                                        onChange={(e) => handleItemChange('colors_back', parseInt(e.target.value) || 0)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 font-mono"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Markup (Multiplicador)</label>
                                    <input
                                        type="number"
                                        step="0.05"
                                        min="1.0"
                                        value={activeItem.markup_multiplier}
                                        onChange={(e) => handleItemChange('markup_multiplier', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 font-mono"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Formato Aberto</label>
                                    <input
                                        type="text"
                                        value={activeItem.format_open}
                                        onChange={(e) => handleItemChange('format_open', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Formato Fechado</label>
                                    <input
                                        type="text"
                                        value={activeItem.format_closed}
                                        onChange={(e) => handleItemChange('format_closed', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Prazo de Produção (Dias)</label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={data.production_lead_time_days}
                                        onChange={(e) => setData('production_lead_time_days', parseInt(e.target.value) || 1)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 font-mono"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Card de Simulação e Precificação em Tempo Real (Zero Float) */}
                    <div className="space-y-6">
                        <div className="bg-gradient-to-br from-slate-900 to-indigo-950/40 border border-indigo-500/30 rounded-2xl p-6 shadow-2xl relative overflow-hidden">
                            <div className="flex items-center justify-between mb-4">
                                <h4 className="font-semibold text-sm text-indigo-300 flex items-center gap-2">
                                    <Calculator className="w-4 h-4 text-indigo-400" />
                                    Simulador Industrial
                                </h4>
                                {simulating && (
                                    <span className="text-[10px] text-indigo-400 animate-pulse font-mono">
                                        Calculando...
                                    </span>
                                )}
                            </div>

                            <div className="space-y-3 text-xs border-b border-slate-800/80 pb-4">
                                <div className="flex justify-between text-slate-400">
                                    <span>Chapas CTP ({simulation?.ctp_plates_count || 0} un):</span>
                                    <span className="font-mono text-slate-200">{formatCurrency(simulation?.ctp_cost)}</span>
                                </div>
                                <div className="flex justify-between text-slate-400">
                                    <span>Substrato / Papel:</span>
                                    <span className="font-mono text-slate-200">{formatCurrency(simulation?.substrate_cost)}</span>
                                </div>
                                <div className="flex justify-between text-slate-400">
                                    <span>Rodagem de Máquina:</span>
                                    <span className="font-mono text-slate-200">{formatCurrency(simulation?.machine_run_cost)}</span>
                                </div>
                                <div className="flex justify-between text-slate-400">
                                    <span>Acabamento & Guilhotina:</span>
                                    <span className="font-mono text-slate-200">{formatCurrency(simulation?.finishing_cost)}</span>
                                </div>
                                <div className="flex justify-between font-semibold text-slate-300 pt-1 border-t border-slate-800/40">
                                    <span>Custo Total Estimado:</span>
                                    <span className="font-mono text-amber-400">{formatCurrency(simulation?.total_estimated_cost)}</span>
                                </div>
                            </div>

                            {/* Preço de Venda & Margem */}
                            <div className="pt-4 space-y-3">
                                <div>
                                    <span className="text-[10px] uppercase font-bold tracking-wider text-slate-400 block mb-1">
                                        Valor Total do Orçamento
                                    </span>
                                    <span className="text-2xl font-extrabold text-emerald-400 font-mono tracking-tight block">
                                        {formatCurrency(simulation?.total_price)}
                                    </span>
                                    <span className="text-xs text-slate-400 font-mono">
                                        Unitário: {formatCurrency(simulation?.unit_price)} / peça
                                    </span>
                                </div>

                                <div className="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-xs space-y-1">
                                    <div className="flex justify-between text-emerald-300">
                                        <span>Lucro Bruto:</span>
                                        <span className="font-mono font-bold">{formatCurrency(simulation?.margin_amount)}</span>
                                    </div>
                                    <div className="flex justify-between text-emerald-400 text-[11px]">
                                        <span>Margem sobre a Venda:</span>
                                        <span className="font-mono font-semibold">
                                            {simulation?.margin_percent ? parseFloat(simulation.margin_percent).toFixed(1) : '0.0'}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
