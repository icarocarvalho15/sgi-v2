import React from 'react';
import { useForm, Link } from '@inertiajs/react';
import AppLayout from '../../../../Layouts/AppLayout';
import { ArrowLeft, Plus, Trash2, Layers, Cpu } from 'lucide-react';

interface WorkCenter {
    id: string;
    name: string;
    code: string;
}

interface OtherBom {
    id: string;
    code: string;
    name: string;
}

interface Props {
    workCenters: WorkCenter[];
    otherBoms: OtherBom[];
    bom?: any;
}

export default function Form({ workCenters, otherBoms }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        code: '',
        name: '',
        product_type: 'commercial_print',
        version: 1,
        parent_bom_id: '',
        notes: '',
        items: [
            {
                item_type: 'substrate',
                name: 'Papel Couché Fosco 150g 66x96',
                quantity: '1.0500',
                unit_of_measure: 'FL',
                scrap_factor_percent: '5.0000',
                work_center_id: workCenters[0]?.id || '',
                child_bom_id: '',
                notes: 'Setup de máquina inclui 50 folhas de acerto',
            },
            {
                item_type: 'ctp_plate',
                name: 'Chapa CTP Térmica 0.30mm Formato 4',
                quantity: '8.0000',
                unit_of_measure: 'UN',
                scrap_factor_percent: '0.0000',
                work_center_id: workCenters[0]?.id || '',
                child_bom_id: '',
                notes: 'Gravação 4 cores frente + 4 cores verso',
            },
        ],
    });

    const addItem = () => {
        setData('items', [
            ...data.items,
            {
                item_type: 'finishing_supply',
                name: '',
                quantity: '1.0000',
                unit_of_measure: 'UN',
                scrap_factor_percent: '0.0000',
                work_center_id: '',
                child_bom_id: '',
                notes: '',
            },
        ]);
    };

    const removeItem = (index: number) => {
        const next = [...data.items];
        next.splice(index, 1);
        setData('items', next);
    };

    const handleItemChange = (index: number, field: string, value: any) => {
        const next = [...data.items];
        next[index] = { ...next[index], [field]: value };
        setData('items', next);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/production/boms');
    };

    return (
        <AppLayout title="Nova Ficha Técnica (BOM)">
            <form onSubmit={handleSubmit} className="max-w-5xl space-y-6">
                <div className="flex items-center justify-between">
                    <Link
                        href="/production/boms"
                        className="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-slate-200 transition-colors"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Voltar para Fichas Técnicas
                    </Link>

                    <button
                        type="submit"
                        disabled={processing}
                        className="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white rounded-xl text-sm font-semibold transition-all shadow-md shadow-indigo-600/20"
                    >
                        {processing ? 'Salvando...' : 'Salvar Ficha Técnica'}
                    </button>
                </div>

                {/* Dados Principais da Ficha Técnica */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-4">
                    <h3 className="text-base font-semibold text-slate-100 flex items-center gap-2 border-b border-slate-800 pb-3">
                        <Layers className="w-4 h-4 text-indigo-400" />
                        Identificação do Produto Gráfico / Brinde
                    </h3>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">Código da BOM *</label>
                            <input
                                type="text"
                                placeholder="Ex: BOM-FOL-001"
                                value={data.code}
                                onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 font-mono"
                            />
                            {errors.code && <span className="text-xs text-rose-400 mt-1 block">{errors.code}</span>}
                        </div>

                        <div className="md:col-span-2">
                            <label className="block text-xs font-medium text-slate-400 mb-1">
                                Nome / Descrição do Produto *
                            </label>
                            <input
                                type="text"
                                placeholder="Ex: Folder 4x4 Couché Fosco 150g com Dobra"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            />
                            {errors.name && <span className="text-xs text-rose-400 mt-1 block">{errors.name}</span>}
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">Categoria de Engenharia</label>
                            <select
                                value={data.product_type}
                                onChange={(e) => setData('product_type', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200"
                            >
                                <option value="commercial_print">Impressão Gráfica (Offset / Digital)</option>
                                <option value="custom_gift">Brinde Personalizado (Laser/Silk)</option>
                                <option value="packaging">Embalagem / Cartucharia</option>
                                <option value="sub_assembly">Subconjunto (ex: Capa, Miolo, Luva)</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">Ficha Técnica Pai (Opcional)</label>
                            <select
                                value={data.parent_bom_id}
                                onChange={(e) => setData('parent_bom_id', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200"
                            >
                                <option value="">Nenhuma (Produto Raiz)</option>
                                {otherBoms.map((b) => (
                                    <option key={b.id} value={b.id}>
                                        {b.code} - {b.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">Versão de Engenharia</label>
                            <input
                                type="number"
                                min="1"
                                value={data.version}
                                onChange={(e) => setData('version', parseInt(e.target.value) || 1)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 font-mono"
                            />
                        </div>
                    </div>
                </div>

                {/* Composição de Insumos da BOM */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-4">
                    <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div>
                            <h3 className="text-base font-semibold text-slate-100 flex items-center gap-2">
                                <Cpu className="w-4 h-4 text-indigo-400" />
                                Lista de Insumos, Chapas CTP e Matérias-Primas
                            </h3>
                            <span className="text-xs text-slate-400">
                                Quantidade consumida por unidade do produto, com fator de quebra/setup.
                            </span>
                        </div>

                        <button
                            type="button"
                            onClick={addItem}
                            className="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300"
                        >
                            <Plus className="w-3.5 h-3.5" />
                            Adicionar Insumo
                        </button>
                    </div>

                    <div className="space-y-3">
                        {data.items.map((item, idx) => (
                            <div
                                key={idx}
                                className="p-4 bg-slate-950/70 border border-slate-800 rounded-xl space-y-3 relative"
                            >
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-semibold text-indigo-400 uppercase tracking-wide">
                                        Item #{idx + 1}
                                    </span>
                                    {data.items.length > 1 && (
                                        <button
                                            type="button"
                                            onClick={() => removeItem(idx)}
                                            className="text-slate-500 hover:text-rose-400 transition-colors"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    )}
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                                    <div>
                                        <label className="block text-[11px] text-slate-400 mb-1">Tipo de Insumo</label>
                                        <select
                                            value={item.item_type}
                                            onChange={(e) => handleItemChange(idx, 'item_type', e.target.value)}
                                            className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200"
                                        >
                                            <option value="substrate">Substrato / Papel</option>
                                            <option value="ctp_plate">Chapa CTP / Fotolito</option>
                                            <option value="ink">Tinta / Verniz</option>
                                            <option value="finishing_supply">Insumo de Acabamento</option>
                                            <option value="sub_assembly">Subconjunto Vinculado</option>
                                        </select>
                                    </div>

                                    <div className="md:col-span-2">
                                        <label className="block text-[11px] text-slate-400 mb-1">
                                            Nome do Componente *
                                        </label>
                                        <input
                                            type="text"
                                            value={item.name}
                                            onChange={(e) => handleItemChange(idx, 'name', e.target.value)}
                                            placeholder="Ex: Papel Couché Fosco 150g"
                                            className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-[11px] text-slate-400 mb-1">
                                            Centro de Trabalho (Máquina)
                                        </label>
                                        <select
                                            value={item.work_center_id}
                                            onChange={(e) => handleItemChange(idx, 'work_center_id', e.target.value)}
                                            className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200"
                                        >
                                            <option value="">Não vinculado</option>
                                            {workCenters.map((wc) => (
                                                <option key={wc.id} value={wc.id}>
                                                    {wc.name} ({wc.code})
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-[11px] text-slate-400 mb-1">
                                            Quantidade por Unidade *
                                        </label>
                                        <input
                                            type="number"
                                            step="0.0001"
                                            value={item.quantity}
                                            onChange={(e) => handleItemChange(idx, 'quantity', e.target.value)}
                                            className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 font-mono"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-[11px] text-slate-400 mb-1">Unidade (U.M.)</label>
                                        <input
                                            type="text"
                                            value={item.unit_of_measure}
                                            onChange={(e) => handleItemChange(idx, 'unit_of_measure', e.target.value.toUpperCase())}
                                            placeholder="FL, UN, KG"
                                            className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 font-mono uppercase"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-[11px] text-slate-400 mb-1">
                                            Quebra Técnica (%)
                                        </label>
                                        <input
                                            type="number"
                                            step="0.1"
                                            value={item.scrap_factor_percent}
                                            onChange={(e) => handleItemChange(idx, 'scrap_factor_percent', e.target.value)}
                                            placeholder="5.0"
                                            className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 font-mono"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-[11px] text-slate-400 mb-1">Observações</label>
                                        <input
                                            type="text"
                                            value={item.notes}
                                            onChange={(e) => handleItemChange(idx, 'notes', e.target.value)}
                                            placeholder="Setup / Ajuste"
                                            className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200"
                                        />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
