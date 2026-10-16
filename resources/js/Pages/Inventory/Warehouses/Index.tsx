import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import { Warehouse, PaginatedResponse } from '@/types';
import InventorySubNav from '../Components/InventorySubNav';
import { 
    Warehouse as WarehouseIcon, 
    Plus, 
    CheckCircle2, 
    XCircle, 
    X, 
    Boxes, 
    MapPin,
    Star
} from 'lucide-react';

interface Props {
    warehouses: PaginatedResponse<Warehouse>;
}

export default function WarehousesIndex({ warehouses }: Props) {
    const [modalOpen, setModalOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        name: '',
        description: '',
        is_active: true,
        is_default: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/app/inventory/warehouses', {
            onSuccess: () => {
                reset();
                setModalOpen(false);
            },
        });
    };

    return (
        <TenantDesktopLayout title="Almoxarifados & Depósitos">
            <Head title="Almoxarifados e Depósitos - SGI v2.0" />

            <div className="space-y-6">
                <InventorySubNav />

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center space-x-2 text-indigo-400 text-xs font-semibold uppercase tracking-wider mb-1">
                            <WarehouseIcon className="w-4 h-4" />
                            <span>Logística Interna</span>
                        </div>
                        <h1 className="text-2xl font-black text-white">Almoxarifados & Depósitos</h1>
                        <p className="text-sm text-slate-400">
                            Estrutura de locais de estocagem para matéria-prima, semi-acabados e produtos acabados
                        </p>
                    </div>

                    <div className="flex items-center space-x-3">
                        <button
                            onClick={() => {
                                reset();
                                setModalOpen(true);
                            }}
                            className="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold px-4 py-2.5 rounded-lg flex items-center space-x-2 shadow-lg shadow-indigo-950/50 transition cursor-pointer"
                        >
                            <Plus className="w-4 h-4" />
                            <span>Novo Almoxarifado</span>
                        </button>
                    </div>
                </div>

                {/* Grid de Cards de Almoxarifados */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {warehouses.data.map((w) => (
                        <div
                            key={w.id}
                            className="bg-slate-900 border border-slate-800 rounded-xl p-5 hover:border-slate-700 transition relative flex flex-col justify-between shadow-lg"
                        >
                            <div>
                                <div className="flex items-start justify-between">
                                    <div className="flex items-center space-x-2.5">
                                        <div className="p-2.5 bg-indigo-500/10 text-indigo-400 rounded-lg">
                                            <WarehouseIcon className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <span className="text-[10px] font-mono font-bold text-indigo-400 uppercase tracking-wider">
                                                [{w.code}]
                                            </span>
                                            <h3 className="text-base font-bold text-white">{w.name}</h3>
                                        </div>
                                    </div>

                                    {w.is_default && (
                                        <span className="inline-flex items-center space-x-1 text-[10px] font-bold text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">
                                            <Star className="w-3 h-3 fill-amber-400" />
                                            <span>Padrão</span>
                                        </span>
                                    )}
                                </div>

                                {w.description && (
                                    <p className="text-xs text-slate-400 mt-3 line-clamp-2">
                                        {w.description}
                                    </p>
                                )}
                            </div>

                            <div className="mt-5 pt-4 border-t border-slate-800 flex items-center justify-between text-xs">
                                <div className="flex items-center space-x-1.5 text-slate-400">
                                    <Boxes className="w-4 h-4 text-slate-500" />
                                    <span>{w.balances_count || 0} itens em saldo</span>
                                </div>

                                <div>
                                    {w.is_active ? (
                                        <span className="text-emerald-400 font-semibold text-[11px] flex items-center space-x-1">
                                            <CheckCircle2 className="w-3.5 h-3.5" />
                                            <span>Ativo</span>
                                        </span>
                                    ) : (
                                        <span className="text-slate-500 font-semibold text-[11px] flex items-center space-x-1">
                                            <XCircle className="w-3.5 h-3.5" />
                                            <span>Inativo</span>
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Modal de Criação de Almoxarifado */}
            {modalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full overflow-hidden shadow-2xl">
                        <div className="flex items-center justify-between p-5 border-b border-slate-800 bg-slate-900/60">
                            <div>
                                <h3 className="text-base font-bold text-white">Cadastrar Almoxarifado</h3>
                                <p className="text-xs text-slate-400">Novo local físico de estoque</p>
                            </div>
                            <button
                                onClick={() => setModalOpen(false)}
                                className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="p-5 space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Código Sigla <span className="text-rose-400">*</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ex: ALM-02, EXP-01"
                                    value={data.code}
                                    onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                    required
                                />
                                {errors.code && <p className="text-xs text-rose-400 mt-1">{errors.code}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Nome do Almoxarifado <span className="text-rose-400">*</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ex: Almoxarifado de Produtos Acabados"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    required
                                />
                                {errors.name && <p className="text-xs text-rose-400 mt-1">{errors.name}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Descrição / Localização Física
                                </label>
                                <textarea
                                    rows={2}
                                    placeholder="Ex: Galpão B - Prateleiras 1 a 12..."
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div className="flex items-center space-x-4 pt-1">
                                <label className="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.is_default}
                                        onChange={(e) => setData('is_default', e.target.checked)}
                                        className="rounded border-slate-700 bg-slate-900 text-amber-500 focus:ring-0"
                                    />
                                    <span>Definir como Depósito Padrão</span>
                                </label>

                                <label className="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-0"
                                    />
                                    <span>Ativo</span>
                                </label>
                            </div>

                            <div className="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setModalOpen(false)}
                                    className="px-4 py-2 rounded-lg text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold px-5 py-2 rounded-lg shadow-lg shadow-indigo-950/50 transition cursor-pointer"
                                >
                                    Salvar Almoxarifado
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </TenantDesktopLayout>
    );
}
