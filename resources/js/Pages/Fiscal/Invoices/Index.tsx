import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import FiscalSubNav from '@/Pages/Fiscal/Components/FiscalSubNav';
import { PaginatedResponse } from '@/types';
import { 
    FileText, 
    Plus, 
    Search, 
    Download, 
    Copy, 
    Check, 
    ExternalLink, 
    Clock, 
    AlertCircle, 
    CheckCircle2, 
    X,
    ReceiptText,
    Send
} from 'lucide-react';

interface Partner {
    id: string;
    name: string;
    document_number?: string;
}

interface SalesOrder {
    id: string;
    order_number: string;
    total_amount: string;
}

interface FiscalInvoice {
    id: string;
    invoice_type: 'NFE' | 'NFSE';
    invoice_number: number;
    series: number;
    environment: 'HOMOLOGATION' | 'PRODUCTION';
    access_key?: string;
    protocol_number?: string;
    status: 'DRAFT' | 'PROCESSING' | 'AUTHORIZED' | 'REJECTED' | 'CANCELLED';
    total_amount: string;
    tax_amount: string;
    xml_path?: string;
    pdf_path?: string;
    rejection_reason?: string;
    issued_at?: string;
    authorized_at?: string;
    partner?: Partner;
    sales_order?: SalesOrder;
}

interface Props {
    invoices: PaginatedResponse<FiscalInvoice>;
    filters: {
        search?: string;
        status?: string;
        type?: string;
    };
    partners: Partner[];
    salesOrders: SalesOrder[];
}

export default function InvoicesIndex({ invoices, filters, partners, salesOrders }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [type, setType] = useState(filters.type || '');
    const [copiedKey, setCopiedKey] = useState<string | null>(null);

    // Modal
    const [isCreateOpen, setIsCreateOpen] = useState(false);

    const createForm = useForm({
        partner_id: partners?.[0]?.id || '',
        sales_order_id: '',
        invoice_type: 'NFE',
        total_amount: '',
    });

    const applyFilters = (newParams: Record<string, string>) => {
        router.get('/app/fiscal/invoices', {
            search,
            status,
            type,
            ...newParams,
        }, { preserveState: true, replace: true });
    };

    const handleCopy = (key: string) => {
        navigator.clipboard.writeText(key);
        setCopiedKey(key);
        setTimeout(() => setCopiedKey(null), 2000);
    };

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/app/fiscal/invoices', {
            onSuccess: () => {
                setIsCreateOpen(false);
                createForm.reset();
            },
        });
    };

    const formatBRL = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num || 0);
    };

    return (
        <TenantDesktopLayout title="Monitoramento Fiscal SEFAZ (NF-e / NFS-e)">
            <Head title="Notas Fiscais Eletrônicas - SGI" />

            <div className="p-6 max-w-7xl mx-auto space-y-6">
                <FiscalSubNav />

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center space-x-3">
                        <div className="p-2.5 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-xl">
                            <ReceiptText className="w-5 h-5" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-white tracking-tight">
                                Notas Fiscais Eletrônicas
                            </h1>
                            <p className="text-xs text-slate-400">
                                Transmissão assíncrona com a SEFAZ, chave de 44 dígitos e geração de XML/DANFE.
                            </p>
                        </div>
                    </div>

                    <button
                        onClick={() => setIsCreateOpen(true)}
                        className="flex items-center space-x-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm transition"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Transmitir Nova NF-e</span>
                    </button>
                </div>

                {/* Barra de Filtros */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col md:flex-row items-center justify-between gap-3 shadow-sm">
                    <div className="relative w-full md:w-80">
                        <Search className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                            type="text"
                            placeholder="Buscar por chave de acesso, cliente ou número..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && applyFilters({ search })}
                            className="w-full pl-9 pr-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-xs text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"
                        />
                    </div>

                    <div className="flex items-center gap-2 w-full md:w-auto">
                        <select
                            value={status}
                            onChange={(e) => {
                                setStatus(e.target.value);
                                applyFilters({ status: e.target.value });
                            }}
                            className="bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-300 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Todos os Status</option>
                            <option value="AUTHORIZED">Autorizadas</option>
                            <option value="PROCESSING">Em Processamento</option>
                            <option value="DRAFT">Rascunhos</option>
                            <option value="REJECTED">Rejeitadas</option>
                            <option value="CANCELLED">Canceladas</option>
                        </select>

                        <select
                            value={type}
                            onChange={(e) => {
                                setType(e.target.value);
                                applyFilters({ type: e.target.value });
                            }}
                            className="bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-300 focus:outline-none focus:border-indigo-500"
                        >
                            <option value="">Todos os Modelos</option>
                            <option value="NFE">NF-e (Mercadorias)</option>
                            <option value="NFSE">NFS-e (Serviços)</option>
                        </select>
                    </div>
                </div>

                {/* Tabela de Notas Fiscais */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-950/60 text-slate-400 font-semibold border-b border-slate-800 uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3 px-4">Número / Série</th>
                                    <th className="py-3 px-4">Modelo</th>
                                    <th className="py-3 px-4">Destinatário (Cliente)</th>
                                    <th className="py-3 px-4">Chave de Acesso (44 dígitos)</th>
                                    <th className="py-3 px-4 text-right">Valor Total</th>
                                    <th className="py-3 px-4 text-center">Status SEFAZ</th>
                                    <th className="py-3 px-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {invoices.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="py-12 text-center text-slate-400">
                                            Nenhuma nota fiscal emitida até o momento.
                                        </td>
                                    </tr>
                                ) : (
                                    invoices.data.map((inv) => (
                                        <tr key={inv.id} className="hover:bg-slate-800/40 transition">
                                            <td className="py-3.5 px-4 font-mono font-bold text-white">
                                                Nº {inv.invoice_number} <span className="text-slate-400 font-normal">/ S.{inv.series}</span>
                                            </td>

                                            <td className="py-3.5 px-4">
                                                <span className="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-500/15 text-indigo-400 border border-indigo-500/20">
                                                    {inv.invoice_type}
                                                </span>
                                            </td>

                                            <td className="py-3.5 px-4">
                                                <div className="font-semibold text-slate-200">
                                                    {inv.partner?.name || 'Cliente'}
                                                </div>
                                                <div className="text-[10px] text-slate-400 font-mono">
                                                    {inv.partner?.document_number}
                                                </div>
                                            </td>

                                            <td className="py-3.5 px-4">
                                                {inv.access_key ? (
                                                    <div className="flex items-center space-x-1.5 font-mono text-[11px] text-slate-300">
                                                        <span>{inv.access_key.slice(0, 4)}...{inv.access_key.slice(-8)}</span>
                                                        <button
                                                            onClick={() => handleCopy(inv.access_key!)}
                                                            className="p-1 text-slate-400 hover:text-white transition"
                                                            title="Copiar Chave Completa"
                                                        >
                                                            {copiedKey === inv.access_key ? (
                                                                <Check className="w-3.5 h-3.5 text-emerald-400" />
                                                            ) : (
                                                                <Copy className="w-3.5 h-3.5" />
                                                            )}
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <span className="text-slate-400 italic">Pendente de emissão</span>
                                                )}
                                            </td>

                                            <td className="py-3.5 px-4 text-right font-bold text-white">
                                                {formatBRL(inv.total_amount)}
                                            </td>

                                            <td className="py-3.5 px-4 text-center">
                                                {inv.status === 'AUTHORIZED' && (
                                                    <span className="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">
                                                        <CheckCircle2 className="w-3 h-3" />
                                                        <span>Autorizada</span>
                                                    </span>
                                                )}
                                                {inv.status === 'PROCESSING' && (
                                                    <span className="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-blue-500/15 text-blue-400 border border-blue-500/20">
                                                        <Clock className="w-3 h-3 animate-spin" />
                                                        <span>Processando</span>
                                                    </span>
                                                )}
                                                {inv.status === 'DRAFT' && (
                                                    <span className="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-400">
                                                        <span>Rascunho</span>
                                                    </span>
                                                )}
                                                {inv.status === 'REJECTED' && (
                                                    <span className="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-500/15 text-rose-400 border border-rose-500/20">
                                                        <AlertCircle className="w-3 h-3" />
                                                        <span>Rejeitada</span>
                                                    </span>
                                                )}
                                                {inv.status === 'CANCELLED' && (
                                                    <span className="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/15 text-amber-400 border border-amber-500/20">
                                                        <span>Cancelada</span>
                                                    </span>
                                                )}
                                            </td>

                                            <td className="py-3.5 px-4 text-right">
                                                <div className="flex items-center justify-end space-x-1.5">
                                                    {inv.status === 'AUTHORIZED' && (
                                                        <>
                                                            <a
                                                                href={`/app/fiscal/invoices/${inv.id}/download-xml`}
                                                                className="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded text-[11px] font-medium transition flex items-center space-x-1"
                                                                title="Download XML"
                                                            >
                                                                <Download className="w-3 h-3" />
                                                                <span>XML</span>
                                                            </a>
                                                            <a
                                                                href={`/app/fiscal/invoices/${inv.id}/download-pdf`}
                                                                className="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded text-[11px] font-medium transition flex items-center space-x-1"
                                                                title="Download DANFE PDF"
                                                            >
                                                                <Download className="w-3 h-3" />
                                                                <span>DANFE</span>
                                                            </a>
                                                        </>
                                                    )}
                                                    <Link
                                                        href={`/app/fiscal/invoices/${inv.id}`}
                                                        className="p-1 text-slate-400 hover:text-white transition"
                                                        title="Ver Detalhes"
                                                    >
                                                        <ExternalLink className="w-4 h-4" />
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Modal Nova Emissão */}
                {isCreateOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                        <div className="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-lg overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                            <div className="p-5 border-b border-slate-800 flex items-center justify-between">
                                <div className="flex items-center space-x-2.5">
                                    <div className="p-2 bg-indigo-500/10 text-indigo-400 rounded-lg">
                                        <Send className="w-5 h-5" />
                                    </div>
                                    <h2 className="text-base font-bold text-white">Transmitir Nota Fiscal</h2>
                                </div>
                                <button onClick={() => setIsCreateOpen(false)} className="text-slate-400 hover:text-white">
                                    <X className="w-5 h-5" />
                                </button>
                            </div>

                            <form onSubmit={handleCreateSubmit} className="p-6 space-y-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">
                                        Destinatário (Cliente) *
                                    </label>
                                    <select
                                        required
                                        value={createForm.data.partner_id}
                                        onChange={(e) => createForm.setData('partner_id', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                    >
                                        {partners.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                {p.name} ({p.document_number})
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">
                                        Vincular a Pedido de Venda (Opcional)
                                    </label>
                                    <select
                                        value={createForm.data.sales_order_id}
                                        onChange={(e) => {
                                            const orderId = e.target.value;
                                            const order = salesOrders.find((o) => o.id === orderId);
                                            createForm.setData((prev) => ({
                                                ...prev,
                                                sales_order_id: orderId,
                                                total_amount: order ? order.total_amount : prev.total_amount,
                                            }));
                                        }}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="">Sem pedido vinculado</option>
                                        {salesOrders.map((o) => (
                                            <option key={o.id} value={o.id}>
                                                {o.order_number} ({formatBRL(o.total_amount)})
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Modelo da Nota *
                                        </label>
                                        <select
                                            value={createForm.data.invoice_type}
                                            onChange={(e) => createForm.setData('invoice_type', e.target.value as any)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        >
                                            <option value="NFE">NF-e (Mercadorias - Mod. 55)</option>
                                            <option value="NFSE">NFS-e (Serviços)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">
                                            Valor Total da Nota (R$) *
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            required
                                            value={createForm.data.total_amount}
                                            onChange={(e) => createForm.setData('total_amount', e.target.value)}
                                            placeholder="0.00"
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-bold"
                                        />
                                    </div>
                                </div>

                                <div className="p-3 bg-indigo-950/30 border border-indigo-800/40 rounded-lg text-xs text-indigo-300 flex items-center space-x-2">
                                    <Clock className="w-4 h-4 text-indigo-400 shrink-0" />
                                    <span>
                                        O processo será assíncrono via <strong>TenantAwareJob</strong> com geração e assinatura de XML no cofre A1.
                                    </span>
                                </div>

                                <div className="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                                    <button
                                        type="button"
                                        onClick={() => setIsCreateOpen(false)}
                                        className="px-4 py-2 text-xs font-medium text-slate-400 hover:text-white"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={createForm.processing}
                                        className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm transition disabled:opacity-50"
                                    >
                                        {createForm.processing ? 'Enviando...' : 'Autorizar e Transmitir'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </TenantDesktopLayout>
    );
}
