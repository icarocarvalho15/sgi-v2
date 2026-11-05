import React from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '../../../../Layouts/AppLayout';
import { Plus, Layers, ChevronRight, Boxes } from 'lucide-react';

interface Bom {
    id: string;
    code: string;
    name: string;
    product_type: string;
    version: number;
    items?: any[];
    parent_bom?: {
        name: string;
    };
}

interface Props {
    boms: {
        data: Bom[];
        links: any[];
        total: number;
    };
}

export default function Index({ boms }: Props) {
    return (
        <AppLayout title="Fichas Técnicas de Produto (BOM)">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm text-slate-400">
                            Engenharia de produtos gráficos e brindes: composição de substratos, chapas CTP, tintas e insumos.
                        </p>
                    </div>

                    <Link
                        href="/production/boms/create"
                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-semibold transition-all shadow-md shadow-indigo-600/20"
                    >
                        <Plus className="w-4 h-4" />
                        Nova Ficha Técnica
                    </Link>
                </div>

                <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-300">
                            <thead className="bg-slate-800/40 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                                <tr>
                                    <th className="px-6 py-4">Código</th>
                                    <th className="px-6 py-4">Descrição do Produto</th>
                                    <th className="px-6 py-4">Classificação</th>
                                    <th className="px-6 py-4">Versão</th>
                                    <th className="px-6 py-4">Insumos / Componentes</th>
                                    <th className="px-6 py-4 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {boms.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-12 text-center text-slate-500">
                                            Nenhuma ficha técnica cadastrada.
                                        </td>
                                    </tr>
                                ) : (
                                    boms.data.map((bom) => (
                                        <tr key={bom.id} className="hover:bg-slate-800/30 transition-colors">
                                            <td className="px-6 py-4 font-mono text-xs font-semibold text-indigo-400">
                                                {bom.code}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="font-medium text-slate-100 block">{bom.name}</span>
                                                {bom.parent_bom && (
                                                    <span className="text-xs text-slate-500 block">
                                                        Subconjunto de: {bom.parent_bom.name}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700">
                                                    {bom.product_type === 'commercial_print'
                                                        ? 'Impressão Gráfica'
                                                        : bom.product_type === 'custom_gift'
                                                        ? 'Brinde Personalizado'
                                                        : bom.product_type === 'sub_assembly'
                                                        ? 'Subconjunto'
                                                        : 'Embalagem'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 font-mono text-xs text-slate-400">
                                                v{bom.version}.0
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-300">
                                                <div className="flex items-center gap-1.5">
                                                    <Boxes className="w-3.5 h-3.5 text-slate-500" />
                                                    <span>{bom.items?.length || 0} componentes</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <Link
                                                    href={`/production/boms/${bom.id}`}
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
