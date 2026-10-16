import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import { Item, UnitOfMeasure, ItemCategory, Warehouse, PaginatedResponse } from '@/types';
import { 
    Boxes, 
    Plus, 
    Search, 
    Filter, 
    Tag, 
    Layers, 
    AlertCircle, 
    CheckCircle2, 
    XCircle, 
    X, 
    Edit,
    ArrowUpDown
} from 'lucide-react';

import InventorySubNav from '../Components/InventorySubNav';

interface Props {
    items: PaginatedResponse<Item>;
    units: UnitOfMeasure[];
    categories: ItemCategory[];
    warehouses: Warehouse[];
    filters: {
        search?: string;
        type?: string;
        category_id?: string;
    };
}

export default function ItemsIndex({ items, units, categories, warehouses, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedType, setSelectedType] = useState(filters.type || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category_id || '');
    const [modalOpen, setModalOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<Item | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        sku: '',
        name: '',
        description: '',
        unit_of_measure_id: units[0]?.id || '',
        category_id: '',
        default_warehouse_id: warehouses[0]?.id || '',
        is_raw_material: false,
        is_manufactured: false,
        is_sellable: true,
        is_service: false,
        allow_negative_stock: false,
        min_stock: '0',
        max_stock: '',
        sale_price: '0.00',
        is_active: true,
    });

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/app/inventory/items', {
            search,
            type: selectedType,
            category_id: selectedCategory,
        }, { preserveState: true });
    };

    const handleTypeSelect = (typeKey: string) => {
        setSelectedType(typeKey);
        router.get('/app/inventory/items', {
            search,
            type: typeKey,
            category_id: selectedCategory,
        }, { preserveState: true });
    };

    const openCreateModal = () => {
        setEditingItem(null);
        reset();
        setData({
            sku: '',
            name: '',
            description: '',
            unit_of_measure_id: units[0]?.id || '',
            category_id: '',
            default_warehouse_id: warehouses[0]?.id || '',
            is_raw_material: false,
            is_manufactured: false,
            is_sellable: true,
            is_service: false,
            allow_negative_stock: false,
            min_stock: '0',
            max_stock: '',
            sale_price: '0.00',
            is_active: true,
        });
        setModalOpen(true);
    };

    const openEditModal = (item: Item) => {
        setEditingItem(item);
        setData({
            sku: item.sku,
            name: item.name,
            description: item.description || '',
            unit_of_measure_id: item.unit_of_measure_id,
            category_id: item.category_id || '',
            default_warehouse_id: item.default_warehouse_id || '',
            is_raw_material: item.is_raw_material,
            is_manufactured: item.is_manufactured,
            is_sellable: item.is_sellable,
            is_service: item.is_service,
            allow_negative_stock: item.allow_negative_stock,
            min_stock: item.min_stock,
            max_stock: item.max_stock || '',
            sale_price: item.sale_price,
            is_active: item.is_active,
        });
        setModalOpen(true);
    };

    const handleSubmitItem = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingItem) {
            put(`/app/inventory/items/${editingItem.id}`, {
                onSuccess: () => setModalOpen(false),
            });
        } else {
            post('/app/inventory/items', {
                onSuccess: () => setModalOpen(false),
            });
        }
    };

    return (
        <TenantDesktopLayout title="Catálogo de Itens & Insumos">
            <Head title="Itens e Insumos Industriais - SGI v2.0" />

            <div className="space-y-6">
                <InventorySubNav />

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center space-x-2 text-indigo-400 text-xs font-semibold uppercase tracking-wider mb-1">
                            <Boxes className="w-4 h-4" />
                            <span>Suprimentos & Almoxarifado</span>
                        </div>
                        <h1 className="text-2xl font-black text-white">Catálogo de Itens & Produtos</h1>
                        <p className="text-sm text-slate-400">
                            Matérias-primas, produtos manufaturados, componentes e serviços cadastrados
                        </p>
                    </div>

                    <div className="flex items-center space-x-3">
                        <button
                            onClick={openCreateModal}
                            className="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold px-4 py-2.5 rounded-lg flex items-center space-x-2 shadow-lg shadow-indigo-950/50 transition cursor-pointer"
                        >
                            <Plus className="w-4 h-4" />
                            <span>Novo Item / Insumo</span>
                        </button>
                    </div>
                </div>

                {/* Filtros e Barra de Busca */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-4">
                    <div className="flex flex-wrap items-center gap-2">
                        {[
                            { key: '', label: 'Todos os Itens' },
                            { key: 'raw_material', label: 'Matérias-Primas' },
                            { key: 'manufactured', label: 'Produtos Fabricados (PA)' },
                            { key: 'sellable', label: 'Itens para Venda' },
                            { key: 'service', label: 'Mão de Obra / Serviços' },
                        ].map((t) => (
                            <button
                                key={t.key}
                                onClick={() => handleTypeSelect(t.key)}
                                className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${
                                    selectedType === t.key
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700'
                                }`}
                            >
                                {t.label}
                            </button>
                        ))}
                    </div>

                    <form onSubmit={handleSearchSubmit} className="flex flex-col sm:flex-row gap-3">
                        <div className="relative flex-1">
                            <Search className="w-4 h-4 absolute left-3.5 top-3 text-slate-400" />
                            <input
                                type="text"
                                placeholder="Buscar por código SKU, nome ou descrição..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full bg-slate-950 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <select
                            value={selectedCategory}
                            onChange={(e) => {
                                setSelectedCategory(e.target.value);
                                router.get('/app/inventory/items', {
                                    search,
                                    type: selectedType,
                                    category_id: e.target.value,
                                }, { preserveState: true });
                            }}
                            className="bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-300 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Todas as Categorias</option>
                            {categories.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>

                        <button
                            type="submit"
                            className="bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition"
                        >
                            Filtrar
                        </button>
                    </form>
                </div>

                {/* Tabela de Itens */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-slate-800 bg-slate-950/60 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                    <th className="py-3 px-4">SKU / Código</th>
                                    <th className="py-3 px-4">Descrição do Item</th>
                                    <th className="py-3 px-4">Tipo</th>
                                    <th className="py-3 px-4 text-center">UM</th>
                                    <th className="py-3 px-4 text-right">Custo Médio (CMP)</th>
                                    <th className="py-3 px-4 text-right">Preço Venda</th>
                                    <th className="py-3 px-4 text-center">Status</th>
                                    <th className="py-3 px-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800 text-xs">
                                {items.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="py-12 text-center text-slate-500">
                                            <Boxes className="w-8 h-8 mx-auto mb-2 opacity-40" />
                                            <p className="text-sm font-semibold">Nenhum item cadastrado ou encontrado nos filtros.</p>
                                        </td>
                                    </tr>
                                ) : (
                                    items.data.map((item) => (
                                        <tr key={item.id} className="hover:bg-slate-800/40 transition">
                                            <td className="py-3 px-4 font-mono font-bold text-indigo-400">
                                                {item.sku}
                                            </td>
                                            <td className="py-3 px-4">
                                                <div className="font-semibold text-white">{item.name}</div>
                                                {item.category && (
                                                    <span className="text-[10px] text-slate-400 flex items-center space-x-1 mt-0.5">
                                                        <Tag className="w-3 h-3" />
                                                        <span>{item.category.name}</span>
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-3 px-4">
                                                <div className="flex flex-wrap gap-1">
                                                    {item.is_raw_material && (
                                                        <span className="bg-amber-500/10 text-amber-400 px-2 py-0.5 rounded text-[10px] font-semibold border border-amber-500/20">
                                                            Matéria-Prima
                                                        </span>
                                                    )}
                                                    {item.is_manufactured && (
                                                        <span className="bg-cyan-500/10 text-cyan-400 px-2 py-0.5 rounded text-[10px] font-semibold border border-cyan-500/20">
                                                            Fabricado (PA)
                                                        </span>
                                                    )}
                                                    {item.is_sellable && (
                                                        <span className="bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded text-[10px] font-semibold border border-emerald-500/20">
                                                            Venda
                                                        </span>
                                                    )}
                                                    {item.is_service && (
                                                        <span className="bg-purple-500/10 text-purple-400 px-2 py-0.5 rounded text-[10px] font-semibold border border-purple-500/20">
                                                            Serviço
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="py-3 px-4 text-center font-mono font-semibold text-slate-300">
                                                {item.unit_of_measure?.symbol || item.unit_of_measure?.code || 'UN'}
                                            </td>
                                            <td className="py-3 px-4 text-right font-mono font-semibold text-emerald-400">
                                                R$ {Number(item.average_cost).toLocaleString('pt-BR', { minimumFractionDigits: 4 })}
                                            </td>
                                            <td className="py-3 px-4 text-right font-mono text-slate-300">
                                                R$ {Number(item.sale_price).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-3 px-4 text-center">
                                                {item.is_active ? (
                                                    <span className="inline-flex items-center text-[10px] font-semibold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                                                        Ativo
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center text-[10px] font-semibold text-slate-400 bg-slate-800 px-2 py-0.5 rounded-full">
                                                        Inativo
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-3 px-4 text-right">
                                                <button
                                                    onClick={() => openEditModal(item)}
                                                    className="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-lg transition"
                                                    title="Editar Item"
                                                >
                                                    <Edit className="w-3.5 h-3.5" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Paginação */}
                    {items.links && items.links.length > 3 && (
                        <div className="p-4 border-t border-slate-800 flex items-center justify-between">
                            <span className="text-xs text-slate-400">
                                Mostrando {items.from || 0} a {items.to || 0} de {items.total} itens
                            </span>
                            <div className="flex space-x-1">
                                {items.links.map((link, i) => (
                                    <button
                                        key={i}
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                        className={`px-3 py-1 rounded text-xs transition ${
                                            link.active
                                                ? 'bg-indigo-600 text-white font-bold'
                                                : link.url
                                                ? 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white'
                                                : 'text-slate-600 cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal de Criação / Edição de Item */}
            {modalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
                        <div className="flex items-center justify-between p-5 border-b border-slate-800 sticky top-0 bg-slate-900 z-10">
                            <div>
                                <h3 className="text-lg font-bold text-white">
                                    {editingItem ? 'Editar Item Industrial' : 'Novo Item / Insumo'}
                                </h3>
                                <p className="text-xs text-slate-400">Cadastre os parâmetros de engenharia e estoque</p>
                            </div>
                            <button
                                onClick={() => setModalOpen(false)}
                                className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmitItem} className="p-6 space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Código SKU <span className="text-rose-400">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Ex: MP-ACO-1020"
                                        value={data.sku}
                                        onChange={(e) => setData('sku', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                        required
                                    />
                                    {errors.sku && <p className="text-xs text-rose-400 mt-1">{errors.sku}</p>}
                                </div>

                                <div className="sm:col-span-2">
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Nome / Descrição Resumida <span className="text-rose-400">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Ex: Chapa de Aço Carbono 1020 3mm"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                        required
                                    />
                                    {errors.name && <p className="text-xs text-rose-400 mt-1">{errors.name}</p>}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Unidade de Medida <span className="text-rose-400">*</span>
                                    </label>
                                    <select
                                        value={data.unit_of_measure_id}
                                        onChange={(e) => setData('unit_of_measure_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                        required
                                    >
                                        {units.map((u) => (
                                            <option key={u.id} value={u.id}>
                                                {u.code} - {u.name} ({u.symbol})
                                            </option>
                                        ))}
                                    </select>
                                    {errors.unit_of_measure_id && (
                                        <p className="text-xs text-rose-400 mt-1">{errors.unit_of_measure_id}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Categoria
                                    </label>
                                    <select
                                        value={data.category_id}
                                        onChange={(e) => setData('category_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="">Sem Categoria</option>
                                        {categories.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Almoxarifado Padrão
                                    </label>
                                    <select
                                        value={data.default_warehouse_id}
                                        onChange={(e) => setData('default_warehouse_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="">Nenhum</option>
                                        {warehouses.map((w) => (
                                            <option key={w.id} value={w.id}>
                                                [{w.code}] {w.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            {/* Flags Industriais */}
                            <div className="p-3 bg-slate-950 rounded-lg border border-slate-800 space-y-2">
                                <label className="block text-xs font-semibold text-slate-300 mb-2">
                                    Classificação Industrial do Item
                                </label>
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                    <label className="flex items-center space-x-2 text-slate-300 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.is_raw_material}
                                            onChange={(e) => setData('is_raw_material', e.target.checked)}
                                            className="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-0"
                                        />
                                        <span>Matéria-Prima</span>
                                    </label>
                                    <label className="flex items-center space-x-2 text-slate-300 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.is_manufactured}
                                            onChange={(e) => setData('is_manufactured', e.target.checked)}
                                            className="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-0"
                                        />
                                        <span>Manufaturado (PA)</span>
                                    </label>
                                    <label className="flex items-center space-x-2 text-slate-300 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.is_sellable}
                                            onChange={(e) => setData('is_sellable', e.target.checked)}
                                            className="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-0"
                                        />
                                        <span>Item de Venda</span>
                                    </label>
                                    <label className="flex items-center space-x-2 text-slate-300 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.is_service}
                                            onChange={(e) => setData('is_service', e.target.checked)}
                                            className="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-0"
                                        />
                                        <span>Serviço / Mão de Obra</span>
                                    </label>
                                </div>
                            </div>

                            {/* Parâmetros de Estoque e Preço */}
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Estoque Mínimo
                                    </label>
                                    <input
                                        type="number"
                                        step="0.0001"
                                        value={data.min_stock}
                                        onChange={(e) => setData('min_stock', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 font-mono focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Estoque Máximo
                                    </label>
                                    <input
                                        type="number"
                                        step="0.0001"
                                        placeholder="Opcional"
                                        value={data.max_stock}
                                        onChange={(e) => setData('max_stock', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 font-mono focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        Preço de Venda Padrão (R$)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.sale_price}
                                        onChange={(e) => setData('sale_price', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 font-mono focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div className="flex items-center space-x-4 pt-2">
                                <label className="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.allow_negative_stock}
                                        onChange={(e) => setData('allow_negative_stock', e.target.checked)}
                                        className="rounded border-slate-700 bg-slate-900 text-rose-600 focus:ring-0"
                                    />
                                    <span>Permitir saldo negativo no Kardex</span>
                                </label>

                                <label className="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-0"
                                    />
                                    <span>Item Ativo</span>
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
                                    {editingItem ? 'Salvar Alterações' : 'Cadastrar Item'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </TenantDesktopLayout>
    );
}
