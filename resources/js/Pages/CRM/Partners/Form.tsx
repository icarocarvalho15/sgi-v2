import React, { useState, useRef } from 'react';
import { useForm, Link } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import { ArrowLeft, Plus, Trash2, Building, User, MapPin, Loader2, CheckCircle2, AlertCircle, Search } from 'lucide-react';

interface Address {
    type: string;
    postal_code: string;
    street: string;
    number: string;
    complement: string;
    neighborhood: string;
    city: string;
    state: string;
    is_default: boolean;
}

interface Props {
    partner?: any;
}

export default function Form({ partner }: Props) {
    const isEdit = Boolean(partner);
    const [cepLoading, setCepLoading] = useState<{ [key: number]: boolean }>({});
    const [cepFeedback, setCepFeedback] = useState<{ [key: number]: { type: 'success' | 'error'; message: string } }>({});
    const numberInputRefs = useRef<{ [key: number]: HTMLInputElement | null }>({});

    const { data, setData, post, put, processing, errors } = useForm({
        type: partner?.type || 'customer',
        person_type: partner?.person_type || 'company',
        name: partner?.name || '',
        trade_name: partner?.trade_name || '',
        document_number: partner?.document_number || '',
        state_registration: partner?.state_registration || '',
        email: partner?.email || '',
        phone: partner?.phone || '',
        whatsapp: partner?.whatsapp || '',
        notes: partner?.notes || '',
        addresses: partner?.addresses?.length
            ? partner.addresses
            : [
                  {
                      type: 'main',
                      postal_code: '',
                      street: '',
                      number: '',
                      complement: '',
                      neighborhood: '',
                      city: '',
                      state: '',
                      is_default: true,
                  },
              ],
    });

    const addAddress = () => {
        setData('addresses', [
            ...data.addresses,
            {
                type: 'shipping',
                postal_code: '',
                street: '',
                number: '',
                complement: '',
                neighborhood: '',
                city: '',
                state: '',
                is_default: false,
            },
        ]);
    };

    const removeAddress = (index: number) => {
        const next = [...data.addresses];
        next.splice(index, 1);
        setData('addresses', next);
    };

    const handleAddressChange = (index: number, field: string, value: any) => {
        const next = [...data.addresses];
        next[index] = { ...next[index], [field]: value };
        setData('addresses', next);
    };

    const fetchAddressByCep = async (rawCep: string, index: number) => {
        const clean = (rawCep || '').replace(/\D/g, '');
        if (clean.length !== 8) return;

        setCepLoading((prev) => ({ ...prev, [index]: true }));
        setCepFeedback((prev) => {
            const next = { ...prev };
            delete next[index];
            return next;
        });

        try {
            // 1. Consulta ViaCEP
            const viaCepRes = await fetch(`https://viacep.com.br/ws/${clean}/json/`);
            if (viaCepRes.ok) {
                const viaData = await viaCepRes.json();
                if (!viaData.erro) {
                    const next = [...data.addresses];
                    next[index] = {
                        ...next[index],
                        street: viaData.logradouro || next[index].street,
                        neighborhood: viaData.bairro || next[index].neighborhood,
                        city: viaData.localidade || next[index].city,
                        state: viaData.uf || next[index].state,
                        complement: next[index].complement || viaData.complemento || '',
                    };
                    setData('addresses', next);

                    setCepFeedback((prev) => ({
                        ...prev,
                        [index]: { type: 'success', message: 'Endereço localizado via CEP!' },
                    }));

                    setTimeout(() => {
                        numberInputRefs.current[index]?.focus();
                    }, 100);
                    return;
                }
            }

            // 2. Fallback para BrasilAPI
            const brasilApiRes = await fetch(`https://brasilapi.com.br/api/cep/v1/${clean}`);
            if (brasilApiRes.ok) {
                const bData = await brasilApiRes.json();
                if (bData.street || bData.city) {
                    const next = [...data.addresses];
                    next[index] = {
                        ...next[index],
                        street: bData.street || next[index].street,
                        neighborhood: bData.neighborhood || next[index].neighborhood,
                        city: bData.city || next[index].city,
                        state: bData.state || next[index].state,
                    };
                    setData('addresses', next);

                    setCepFeedback((prev) => ({
                        ...prev,
                        [index]: { type: 'success', message: 'Endereço localizado via CEP!' },
                    }));

                    setTimeout(() => {
                        numberInputRefs.current[index]?.focus();
                    }, 100);
                    return;
                }
            }

            setCepFeedback((prev) => ({
                ...prev,
                [index]: { type: 'error', message: 'CEP não encontrado. Preencha manualmente.' },
            }));
        } catch (error) {
            setCepFeedback((prev) => ({
                ...prev,
                [index]: { type: 'error', message: 'Falha ao buscar CEP. Preencha manualmente.' },
            }));
        } finally {
            setCepLoading((prev) => ({ ...prev, [index]: false }));
        }
    };

    const handleCepChange = (index: number, value: string) => {
        const clean = value.replace(/\D/g, '').slice(0, 8);
        const formatted = clean.length > 5 ? `${clean.slice(0, 5)}-${clean.slice(5)}` : clean;

        handleAddressChange(index, 'postal_code', formatted);

        if (clean.length === 8) {
            fetchAddressByCep(clean, index);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(`/crm/partners/${partner.id}`);
        } else {
            post('/crm/partners');
        }
    };

    return (
        <AppLayout title={isEdit ? 'Editar Parceiro' : 'Novo Cliente / Fornecedor'}>
            <form onSubmit={handleSubmit} className="max-w-4xl space-y-6">
                <div className="flex items-center justify-between">
                    <Link
                        href="/crm/partners"
                        className="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-slate-200 transition-colors"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Voltar para listagem
                    </Link>

                    <button
                        type="submit"
                        disabled={processing}
                        className="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white rounded-xl text-sm font-semibold transition-all shadow-md shadow-indigo-600/20"
                    >
                        {processing ? 'Salvando...' : isEdit ? 'Atualizar Parceiro' : 'Cadastrar Parceiro'}
                    </button>
                </div>

                {/* Dados Principais */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-5">
                    <h3 className="text-base font-semibold text-slate-100 flex items-center gap-2 border-b border-slate-800 pb-3">
                        <Building className="w-4 h-4 text-indigo-400" />
                        Identificação Geral
                    </h3>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">Tipo de Parceiro</label>
                            <select
                                value={data.type}
                                onChange={(e) => setData('type', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="customer">Cliente</option>
                                <option value="supplier">Fornecedor (Papéis/Chapas/Tintas)</option>
                                <option value="both">Ambos (Cliente & Fornecedor)</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">Pessoa</label>
                            <select
                                value={data.person_type}
                                onChange={(e) => setData('person_type', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="company">Pessoa Jurídica (CNPJ)</option>
                                <option value="individual">Pessoa Física (CPF)</option>
                            </select>
                        </div>

                        <div className="md:col-span-2">
                            <label className="block text-xs font-medium text-slate-400 mb-1">
                                Razão Social / Nome Completo *
                            </label>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            />
                            {errors.name && <span className="text-xs text-rose-400 mt-1 block">{errors.name}</span>}
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">Nome Fantasia</label>
                            <input
                                type="text"
                                value={data.trade_name}
                                onChange={(e) => setData('trade_name', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">
                                {data.person_type === 'company' ? 'CNPJ *' : 'CPF *'}
                            </label>
                            <input
                                type="text"
                                value={data.document_number}
                                onChange={(e) => setData('document_number', e.target.value)}
                                placeholder={data.person_type === 'company' ? '00.000.000/0000-00' : '000.000.000-00'}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 font-mono"
                            />
                            {errors.document_number && (
                                <span className="text-xs text-rose-400 mt-1 block">{errors.document_number}</span>
                            )}
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">Inscrição Estadual (IE)</label>
                            <input
                                type="text"
                                value={data.state_registration}
                                onChange={(e) => setData('state_registration', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">E-mail Comercial</label>
                            <input
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-400 mb-1">Telefone / WhatsApp</label>
                            <input
                                type="text"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                            />
                        </div>
                    </div>
                </div>

                {/* Múltiplos Endereços */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-4">
                    <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 className="text-base font-semibold text-slate-100 flex items-center gap-2">
                            <MapPin className="w-4 h-4 text-indigo-400" />
                            Endereços (Faturamento & Entrega)
                        </h3>
                        <button
                            type="button"
                            onClick={addAddress}
                            className="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-400 hover:text-indigo-300"
                        >
                            <Plus className="w-3.5 h-3.5" />
                            Adicionar Endereço
                        </button>
                    </div>

                    {data.addresses.map((addr: Address, idx: number) => (
                        <div key={idx} className="p-4 bg-slate-950/70 border border-slate-800 rounded-xl space-y-3 relative">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-semibold text-indigo-400 uppercase tracking-wide">
                                    Endereço #{idx + 1}
                                </span>
                                {data.addresses.length > 1 && (
                                    <button
                                        type="button"
                                        onClick={() => removeAddress(idx)}
                                        className="text-slate-500 hover:text-rose-400 transition-colors"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                )}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label className="block text-[11px] text-slate-400 mb-1">Finalidade</label>
                                    <select
                                        value={addr.type}
                                        onChange={(e) => handleAddressChange(idx, 'type', e.target.value)}
                                        className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200"
                                    >
                                        <option value="main">Principal</option>
                                        <option value="billing">Cobrança</option>
                                        <option value="shipping">Entrega de Brindes/Impressos</option>
                                    </select>
                                </div>

                                <div>
                                    <div className="flex items-center justify-between mb-1">
                                        <label className="block text-[11px] text-slate-400">CEP *</label>
                                        {cepLoading[idx] && (
                                            <span className="text-[10px] text-indigo-400 flex items-center gap-1 font-medium animate-pulse">
                                                <Loader2 className="w-3 h-3 animate-spin" />
                                                Buscando endereço...
                                            </span>
                                        )}
                                    </div>
                                    <div className="relative flex items-center">
                                        <input
                                            type="text"
                                            value={addr.postal_code}
                                            onChange={(e) => handleCepChange(idx, e.target.value)}
                                            onBlur={() => {
                                                const clean = (addr.postal_code || '').replace(/\D/g, '');
                                                if (clean.length === 8 && !cepFeedback[idx]) {
                                                    fetchAddressByCep(clean, idx);
                                                }
                                            }}
                                            placeholder="00000-000"
                                            className="w-full bg-slate-900 border border-slate-800 rounded-lg pl-2.5 pr-8 py-1.5 text-xs text-slate-200 font-mono focus:outline-none focus:border-indigo-500"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => fetchAddressByCep(addr.postal_code, idx)}
                                            disabled={cepLoading[idx]}
                                            title="Consultar endereço via CEP"
                                            className="absolute right-2 text-slate-400 hover:text-indigo-400 disabled:opacity-50 transition-colors"
                                        >
                                            {cepLoading[idx] ? (
                                                <Loader2 className="w-3.5 h-3.5 animate-spin text-indigo-400" />
                                            ) : cepFeedback[idx]?.type === 'success' ? (
                                                <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400" />
                                            ) : (
                                                <Search className="w-3.5 h-3.5" />
                                            )}
                                        </button>
                                    </div>
                                    {cepFeedback[idx] && (
                                        <span
                                            className={`text-[10px] mt-1 flex items-center gap-1 ${
                                                cepFeedback[idx].type === 'success' ? 'text-emerald-400' : 'text-amber-400'
                                            }`}
                                        >
                                            {cepFeedback[idx].type === 'success' ? (
                                                <CheckCircle2 className="w-3 h-3 shrink-0" />
                                            ) : (
                                                <AlertCircle className="w-3 h-3 shrink-0" />
                                            )}
                                            {cepFeedback[idx].message}
                                        </span>
                                    )}
                                </div>

                                <div className="md:col-span-2">
                                    <label className="block text-[11px] text-slate-400 mb-1">Logradouro (Rua/Av) *</label>
                                    <input
                                        type="text"
                                        value={addr.street}
                                        onChange={(e) => handleAddressChange(idx, 'street', e.target.value)}
                                        placeholder="Ex: Av. Brasil ou Rua das Indústrias"
                                        className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-[11px] text-slate-400 mb-1">Número *</label>
                                    <input
                                        type="text"
                                        ref={(el) => { numberInputRefs.current[idx] = el; }}
                                        value={addr.number}
                                        onChange={(e) => handleAddressChange(idx, 'number', e.target.value)}
                                        placeholder="123"
                                        className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-[11px] text-slate-400 mb-1">Complemento</label>
                                    <input
                                        type="text"
                                        value={addr.complement}
                                        onChange={(e) => handleAddressChange(idx, 'complement', e.target.value)}
                                        placeholder="Ex: Sala 402, Galpão B"
                                        className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-[11px] text-slate-400 mb-1">Bairro *</label>
                                    <input
                                        type="text"
                                        value={addr.neighborhood}
                                        onChange={(e) => handleAddressChange(idx, 'neighborhood', e.target.value)}
                                        placeholder="Ex: Centro ou Distrito Industrial"
                                        className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-[11px] text-slate-400 mb-1">Cidade *</label>
                                    <input
                                        type="text"
                                        value={addr.city}
                                        onChange={(e) => handleAddressChange(idx, 'city', e.target.value)}
                                        placeholder="Ex: São Paulo"
                                        className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-[11px] text-slate-400 mb-1">UF (Estado) *</label>
                                    <input
                                        type="text"
                                        maxLength={2}
                                        value={addr.state}
                                        onChange={(e) => handleAddressChange(idx, 'state', e.target.value.toUpperCase())}
                                        placeholder="SP"
                                        className="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 uppercase font-mono focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </form>
        </AppLayout>
    );
}
