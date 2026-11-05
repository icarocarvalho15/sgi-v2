import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import { UserPlus, Search, Building2, User, Phone, Mail, MapPin } from 'lucide-react';

interface Address {
    id: string;
    city: string;
    state: string;
    type: string;
}

interface Partner {
    id: string;
    name: string;
    trade_name: string | null;
    document_number: string;
    type: 'customer' | 'supplier' | 'both';
    person_type: 'individual' | 'company';
    email: string | null;
    phone: string | null;
    addresses: Address[];
}

interface Props {
    partners: {
        data: Partner[];
        links: any[];
        total: number;
    };
    filters: {
        type?: string;
        search?: string;
    };
}

export default function Index({ partners, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [type, setType] = useState(filters.type || '');

    const handleFilter = (newType?: string) => {
        const filterType = newType !== undefined ? newType : type;
        router.get('/crm/partners', { search, type: filterType }, { preserveState: true });
    };

    return (
        <AppLayout title="Clientes & Fornecedores">
            <div className="space-y-6">
                {/* Actions & Filters */}
                <div className="flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div className="flex flex-1 gap-3 w-full sm:w-auto">
                        <div className="relative flex-1 max-w-md">
                            <Search className="w-4 h-4 absolute left-3 top-3 text-slate-400" />
                            <input
                                type="text"
                                placeholder="Buscar por Razão Social, Fantasia ou CNPJ..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                                className="w-full bg-slate-900 border border-slate-800 rounded-xl pl-9 pr-4 py-2 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors"
                            />
                        </div>

                        <select
                            value={type}
                            onChange={(e) => {
                                setType(e.target.value);
                                handleFilter(e.target.value);
                            }}
                            className="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors"
                        >
                            <option value="">Todos os Tipos</option>
                            <option value="customer">Apenas Clientes</option>
                            <option value="supplier">Apenas Fornecedores</option>
                        </select>
                    </div>

                    <Link
                        href="/crm/partners/create"
                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-medium transition-all shadow-md shadow-indigo-600/20"
                    >
                        <UserPlus className="w-4 h-4" />
                        Novo Parceiro
                    </Link>
                </div>

                {/* Partners List Table */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-300">
                            <thead className="bg-slate-800/40 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                                <tr>
                                    <th className="px-6 py-4">Parceiro</th>
                                    <th className="px-6 py-4">Documento Fiscal</th>
                                    <th className="px-6 py-4">Classificação</th>
                                    <th className="px-6 py-4">Contato</th>
                                    <th className="px-6 py-4">Localidade</th>
                                    <th className="px-6 py-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {partners.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-12 text-center text-slate-500">
                                            Nenhum cliente ou fornecedor cadastrado.
                                        </td>
                                    </tr>
                                ) : (
                                    partners.data.map((partner) => (
                                        <tr key={partner.id} className="hover:bg-slate-800/30 transition-colors">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-9 h-9 rounded-lg bg-slate-800 border border-slate-700/60 flex items-center justify-center text-slate-400">
                                                        {partner.person_type === 'company' ? (
                                                            <Building2 className="w-4 h-4" />
                                                        ) : (
                                                            <User className="w-4 h-4" />
                                                        )}
                                                    </div>
                                                    <div>
                                                        <span className="font-medium text-slate-100 block">{partner.name}</span>
                                                        {partner.trade_name && (
                                                            <span className="text-xs text-slate-400 block">{partner.trade_name}</span>
                                                        )}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 font-mono text-xs text-slate-300">
                                                {partner.document_number}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span
                                                    className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border ${
                                                        partner.type === 'customer'
                                                            ? 'bg-blue-500/10 text-blue-400 border-blue-500/20'
                                                            : partner.type === 'supplier'
                                                            ? 'bg-amber-500/10 text-amber-400 border-amber-500/20'
                                                            : 'bg-purple-500/10 text-purple-400 border-purple-500/20'
                                                    }`}
                                                >
                                                    {partner.type === 'customer'
                                                        ? 'Cliente'
                                                        : partner.type === 'supplier'
                                                        ? 'Fornecedor'
                                                        : 'Cliente & Fornecedor'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-xs space-y-1">
                                                {partner.email && (
                                                    <div className="flex items-center gap-1.5 text-slate-300">
                                                        <Mail className="w-3.5 h-3.5 text-slate-500" />
                                                        <span>{partner.email}</span>
                                                    </div>
                                                )}
                                                {partner.phone && (
                                                    <div className="flex items-center gap-1.5 text-slate-400">
                                                        <Phone className="w-3.5 h-3.5 text-slate-500" />
                                                        <span>{partner.phone}</span>
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-400">
                                                {partner.addresses && partner.addresses.length > 0 ? (
                                                    <div className="flex items-center gap-1">
                                                        <MapPin className="w-3.5 h-3.5 text-slate-500" />
                                                        <span>
                                                            {partner.addresses[0].city} / {partner.addresses[0].state}
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="text-slate-600">—</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <Link
                                                    href={`/crm/partners/${partner.id}/edit`}
                                                    className="text-xs font-medium text-indigo-400 hover:text-indigo-300 transition-colors"
                                                >
                                                    Editar
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
