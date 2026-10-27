import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import CommercialSubNav from '../Components/CommercialSubNav';
import { PaginatedResponse } from '@/types';
import { 
    Users, 
    Plus, 
    Search, 
    Building2, 
    User, 
    MapPin, 
    Phone, 
    Mail, 
    X, 
    CheckCircle2,
    Briefcase
} from 'lucide-react';

interface PartnerAddress {
    id: string;
    address_type: string;
    postal_code: string;
    street: string;
    number: string;
    complement?: string;
    district: string;
    city: string;
    state: string;
    is_default: boolean;
}

interface Partner {
    id: string;
    partner_type: 'CUSTOMER' | 'SUPPLIER' | 'BOTH';
    person_type: 'LEGAL_ENTITY' | 'INDIVIDUAL';
    document_number: string;
    state_registration?: string;
    name: string;
    trade_name?: string;
    email?: string;
    phone?: string;
    notes?: string;
    is_active: boolean;
    addresses?: PartnerAddress[];
}

interface Props {
    partners: PaginatedResponse<Partner>;
    filters: {
        search?: string;
        type?: string;
    };
}

export default function PartnersIndex({ partners, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [type, setType] = useState(filters.type || '');
    const [isCreateOpen, setIsCreateOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        partner_type: 'CUSTOMER',
        person_type: 'LEGAL_ENTITY',
        document_number: '',
        state_registration: '',
        name: '',
        trade_name: '',
        email: '',
        phone: '',
        notes: '',
        is_active: true,
        address: {
            postal_code: '',
            street: '',
            number: '',
            complement: '',
            district: '',
            city: '',
            state: 'SP',
        },
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/app/crm/partners', { search, type }, { preserveState: true });
    };

    const handleTypeFilter = (newType: string) => {
        setType(newType);
        router.get('/app/crm/partners', { search, type: newType }, { preserveState: true });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/app/crm/partners', {
            onSuccess: () => {
                setIsCreateOpen(false);
                reset();
            },
        });
    };

    const getPartnerTypeBadge = (partnerType: string) => {
        switch (partnerType) {
            case 'CUSTOMER':
                return <span className="px-2 py-0.5 text-xs font-semibold rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Cliente</span>;
            case 'SUPPLIER':
                return <span className="px-2 py-0.5 text-xs font-semibold rounded bg-blue-500/10 text-blue-400 border border-blue-500/20">Fornecedor</span>;
            case 'BOTH':
                return <span className="px-2 py-0.5 text-xs font-semibold rounded bg-purple-500/10 text-purple-400 border border-purple-500/20">Cliente & Fornecedor</span>;
            default:
                return null;
        }
    };

    return (
        <TenantDesktopLayout title="Parceiros Comerciais - CRM">
            <Head title="Clientes & Fornecedores - CRM" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <CommercialSubNav />

                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-xl font-bold text-white flex items-center gap-2">
                            <Users className="w-6 h-6 text-indigo-400" />
                            Clientes & Fornecedores
                        </h1>
                        <p className="text-xs text-slate-400 mt-1">
                            Gestão de cadastros de parceiros com validação de CNPJ/CPF, endereços múltiplos e histórico comercial.
                        </p>
                    </div>

                    <button
                        onClick={() => setIsCreateOpen(true)}
                        className="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm"
                    >
                        <Plus className="w-4 h-4" />
                        Novo Parceiro
                    </button>
                </div>

                {/* Filtros e Busca */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
                    <form onSubmit={handleSearch} className="flex-1 w-full flex items-center gap-2">
                        <div className="relative flex-1">
                            <Search className="w-4 h-4 text-slate-500 absolute left-3 top-3" />
                            <input
                                type="text"
                                placeholder="Buscar por Razão Social, Fantasia, CNPJ/CPF ou Email..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-9 pr-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition"
                            />
                        </div>
                        <button
                            type="submit"
                            className="bg-slate-800 hover:bg-slate-700 text-slate-200 px-4 py-2 rounded-lg text-sm font-medium transition"
                        >
                            Filtrar
                        </button>
                    </form>

                    <div className="flex items-center gap-1.5 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
                        {[
                            { key: '', label: 'Todos' },
                            { key: 'CUSTOMER', label: 'Clientes' },
                            { key: 'SUPPLIER', label: 'Fornecedores' },
                            { key: 'BOTH', label: 'Ambos' },
                        ].map((btn) => (
                            <button
                                key={btn.key}
                                onClick={() => handleTypeFilter(btn.key)}
                                className={`px-3 py-1.5 rounded-lg text-xs font-medium transition whitespace-nowrap ${
                                    type === btn.key
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-800'
                                }`}
                            >
                                {btn.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Tabela de Parceiros */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs text-slate-300">
                            <thead className="bg-slate-950/70 border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th className="py-3 px-4">Parceiro Comercial</th>
                                    <th className="py-3 px-4">Tipo</th>
                                    <th className="py-3 px-4">CNPJ / CPF</th>
                                    <th className="py-3 px-4">Contato</th>
                                    <th className="py-3 px-4">Localização</th>
                                    <th className="py-3 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {partners.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="py-12 text-center text-slate-500">
                                            Nenhum parceiro encontrado com os filtros aplicados.
                                        </td>
                                    </tr>
                                ) : (
                                    partners.data.map((partner) => {
                                        const defaultAddr = partner.addresses?.find((a) => a.is_default) || partner.addresses?.[0];

                                        return (
                                            <tr key={partner.id} className="hover:bg-slate-800/40 transition">
                                                <td className="py-3 px-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center shrink-0">
                                                            {partner.person_type === 'LEGAL_ENTITY' ? (
                                                                <Building2 className="w-4 h-4 text-indigo-400" />
                                                            ) : (
                                                                <User className="w-4 h-4 text-indigo-400" />
                                                            )}
                                                        </div>
                                                        <div>
                                                            <div className="font-bold text-white">{partner.name}</div>
                                                            {partner.trade_name && (
                                                                <div className="text-[11px] text-slate-400">{partner.trade_name}</div>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4">
                                                    {getPartnerTypeBadge(partner.partner_type)}
                                                </td>
                                                <td className="py-3 px-4 font-mono text-slate-300">
                                                    {partner.document_number}
                                                </td>
                                                <td className="py-3 px-4">
                                                    <div className="space-y-0.5">
                                                        {partner.email && (
                                                            <div className="flex items-center gap-1.5 text-slate-300">
                                                                <Mail className="w-3 h-3 text-slate-500" />
                                                                <span>{partner.email}</span>
                                                            </div>
                                                        )}
                                                        {partner.phone && (
                                                            <div className="flex items-center gap-1.5 text-slate-400">
                                                                <Phone className="w-3 h-3 text-slate-500" />
                                                                <span>{partner.phone}</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4">
                                                    {defaultAddr ? (
                                                        <div className="flex items-center gap-1.5 text-slate-300">
                                                            <MapPin className="w-3 h-3 text-slate-500 shrink-0" />
                                                            <span>{defaultAddr.city}/{defaultAddr.state}</span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-slate-500">—</span>
                                                    )}
                                                </td>
                                                <td className="py-3 px-4 text-center">
                                                    <span className={`inline-block px-2 py-0.5 text-[10px] font-semibold rounded ${
                                                        partner.is_active
                                                            ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                                                            : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'
                                                    }`}>
                                                        {partner.is_active ? 'Ativo' : 'Inativo'}
                                                    </span>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Modal Novo Parceiro */}
            {isCreateOpen && (
                <div className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
                        <div className="p-6 border-b border-slate-800 flex items-center justify-between sticky top-0 bg-slate-900 z-10">
                            <div>
                                <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                    <Briefcase className="w-5 h-5 text-indigo-400" />
                                    Cadastrar Parceiro Comercial
                                </h3>
                                <p className="text-xs text-slate-400 mt-0.5">Preencha os dados cadastrais e endereço principal.</p>
                            </div>
                            <button
                                onClick={() => setIsCreateOpen(false)}
                                className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-5">
                            {/* Classificação */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Relação Comercial *</label>
                                    <select
                                        value={data.partner_type}
                                        onChange={(e) => setData('partner_type', e.target.value as any)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="CUSTOMER">Cliente</option>
                                        <option value="SUPPLIER">Fornecedor</option>
                                        <option value="BOTH">Ambos (Cliente & Fornecedor)</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Tipo de Pessoa *</label>
                                    <select
                                        value={data.person_type}
                                        onChange={(e) => setData('person_type', e.target.value as any)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="LEGAL_ENTITY">Pessoa Jurídica (CNPJ)</option>
                                        <option value="INDIVIDUAL">Pessoa Física (CPF)</option>
                                    </select>
                                </div>
                            </div>

                            {/* Documentos & Nome */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        {data.person_type === 'LEGAL_ENTITY' ? 'CNPJ *' : 'CPF *'}
                                    </label>
                                    <input
                                        type="text"
                                        placeholder={data.person_type === 'LEGAL_ENTITY' ? '00.000.000/0001-00' : '000.000.000-00'}
                                        value={data.document_number}
                                        onChange={(e) => setData('document_number', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 font-mono"
                                        required
                                    />
                                    {errors.document_number && <p className="text-xs text-rose-400 mt-1">{errors.document_number}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Inscrição Estadual</label>
                                    <input
                                        type="text"
                                        placeholder="Isento ou número..."
                                        value={data.state_registration}
                                        onChange={(e) => setData('state_registration', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">
                                        {data.person_type === 'LEGAL_ENTITY' ? 'Razão Social *' : 'Nome Completo *'}
                                    </label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                        required
                                    />
                                    {errors.name && <p className="text-xs text-rose-400 mt-1">{errors.name}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Nome Fantasia</label>
                                    <input
                                        type="text"
                                        value={data.trade_name}
                                        onChange={(e) => setData('trade_name', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            {/* Contatos */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">E-mail Principal</label>
                                    <input
                                        type="email"
                                        placeholder="contato@empresa.com.br"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                    {errors.email && <p className="text-xs text-rose-400 mt-1">{errors.email}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Telefone / WhatsApp</label>
                                    <input
                                        type="text"
                                        placeholder="(11) 98765-4321"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            {/* Endereço */}
                            <div className="pt-2 border-t border-slate-800/80">
                                <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Endereço Principal</h4>
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                                    <div>
                                        <label className="block text-[11px] text-slate-400 mb-1">CEP</label>
                                        <input
                                            type="text"
                                            placeholder="00000-000"
                                            value={data.address.postal_code}
                                            onChange={(e) => setData('address', { ...data.address, postal_code: e.target.value })}
                                            className="w-full bg-slate-950 border border-slate-800 rounded px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                    <div className="sm:col-span-2">
                                        <label className="block text-[11px] text-slate-400 mb-1">Logradouro (Rua/Avenida)</label>
                                        <input
                                            type="text"
                                            value={data.address.street}
                                            onChange={(e) => setData('address', { ...data.address, street: e.target.value })}
                                            className="w-full bg-slate-950 border border-slate-800 rounded px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div>
                                        <label className="block text-[11px] text-slate-400 mb-1">Número</label>
                                        <input
                                            type="text"
                                            value={data.address.number}
                                            onChange={(e) => setData('address', { ...data.address, number: e.target.value })}
                                            className="w-full bg-slate-950 border border-slate-800 rounded px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-[11px] text-slate-400 mb-1">Bairro</label>
                                        <input
                                            type="text"
                                            value={data.address.district}
                                            onChange={(e) => setData('address', { ...data.address, district: e.target.value })}
                                            className="w-full bg-slate-950 border border-slate-800 rounded px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-[11px] text-slate-400 mb-1">Cidade</label>
                                        <input
                                            type="text"
                                            value={data.address.city}
                                            onChange={(e) => setData('address', { ...data.address, city: e.target.value })}
                                            className="w-full bg-slate-950 border border-slate-800 rounded px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-[11px] text-slate-400 mb-1">UF</label>
                                        <input
                                            type="text"
                                            maxLength={2}
                                            value={data.address.state}
                                            onChange={(e) => setData('address', { ...data.address, state: e.target.value.toUpperCase() })}
                                            className="w-full bg-slate-950 border border-slate-800 rounded px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 uppercase"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setIsCreateOpen(false)}
                                    className="px-4 py-2 rounded-lg text-sm font-medium text-slate-400 hover:text-white transition"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2 rounded-lg text-sm font-semibold transition disabled:opacity-50"
                                >
                                    {processing ? 'Salvando...' : 'Salvar Parceiro'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </TenantDesktopLayout>
    );
}
