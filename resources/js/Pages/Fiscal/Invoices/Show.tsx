import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import FiscalSubNav from '@/Pages/Fiscal/Components/FiscalSubNav';
import { 
    ArrowLeft, 
    Download, 
    Copy, 
    Check, 
    CheckCircle2, 
    AlertCircle, 
    Clock, 
    Building2, 
    FileCode, 
    FileText,
    ReceiptText,
    ShieldCheck
} from 'lucide-react';

interface Partner {
    id: string;
    name: string;
    document_number?: string;
    email?: string;
    phone?: string;
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
    invoice: FiscalInvoice;
}

export default function InvoiceShow({ invoice }: Props) {
    const [copiedKey, setCopiedKey] = useState(false);

    const handleCopy = (key: string) => {
        navigator.clipboard.writeText(key);
        setCopiedKey(true);
        setTimeout(() => setCopiedKey(false), 2000);
    };

    const formatBRL = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num || 0);
    };

    return (
        <TenantDesktopLayout title={`Nota Fiscal Nº ${invoice.invoice_number}`}>
            <Head title={`Nota Fiscal Nº ${invoice.invoice_number} - SGI`} />

            <div className="p-6 max-w-7xl mx-auto space-y-6">
                <FiscalSubNav />

                {/* Topbar com navegação */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                        <Link
                            href="/app/fiscal/invoices"
                            className="p-2 text-slate-400 hover:text-white bg-slate-900 border border-slate-800 rounded-lg transition"
                        >
                            <ArrowLeft className="w-4 h-4" />
                        </Link>
                        <div>
                            <div className="flex items-center space-x-2">
                                <h1 className="text-xl font-bold text-white font-mono">
                                    {invoice.invoice_type} Nº {invoice.invoice_number} / Série {invoice.series}
                                </h1>
                                {invoice.status === 'AUTHORIZED' && (
                                    <span className="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">
                                        <CheckCircle2 className="w-3 h-3" />
                                        <span>Autorizada</span>
                                    </span>
                                )}
                                {invoice.status === 'PROCESSING' && (
                                    <span className="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-blue-500/15 text-blue-400 border border-blue-500/20">
                                        <Clock className="w-3 h-3 animate-spin" />
                                        <span>Processando</span>
                                    </span>
                                )}
                                {invoice.status === 'REJECTED' && (
                                    <span className="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-500/15 text-rose-400 border border-rose-500/20">
                                        <AlertCircle className="w-3 h-3" />
                                        <span>Rejeitada</span>
                                    </span>
                                )}
                            </div>
                            <p className="text-xs text-slate-400">
                                Ambiente: {invoice.environment === 'HOMOLOGATION' ? 'Homologação (Sem Valor Fiscal)' : 'Produção Nacional SEFAZ'}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center space-x-2">
                        {invoice.status === 'AUTHORIZED' && (
                            <>
                                <a
                                    href={`/app/fiscal/invoices/${invoice.id}/download-xml`}
                                    className="flex items-center space-x-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-lg transition"
                                >
                                    <Download className="w-4 h-4" />
                                    <span>Download XML</span>
                                </a>
                                <a
                                    href={`/app/fiscal/invoices/${invoice.id}/download-pdf`}
                                    className="flex items-center space-x-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm transition"
                                >
                                    <Download className="w-4 h-4" />
                                    <span>Download DANFE PDF</span>
                                </a>
                            </>
                        )}
                    </div>
                </div>

                {/* Chave de Acesso Box */}
                {invoice.access_key && (
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm space-y-2">
                        <span className="text-xs text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                            Chave de Acesso SEFAZ (44 Dígitos)
                        </span>
                        <div className="flex items-center justify-between p-3 bg-slate-950 border border-slate-800 rounded-lg">
                            <span className="font-mono text-xs text-indigo-400 font-bold tracking-wider select-all break-all">
                                {invoice.access_key}
                            </span>
                            <button
                                onClick={() => handleCopy(invoice.access_key!)}
                                className="flex items-center space-x-1 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded text-xs font-semibold transition ml-3 shrink-0"
                            >
                                {copiedKey ? (
                                    <>
                                        <Check className="w-3.5 h-3.5 text-emerald-400" />
                                        <span>Copiado!</span>
                                    </>
                                ) : (
                                    <>
                                        <Copy className="w-3.5 h-3.5" />
                                        <span>Copiar</span>
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                )}

                {/* Cards de Resumo */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                        <span className="text-xs text-slate-400">Valor Total dos Produtos / NF</span>
                        <p className="text-xl font-bold text-white mt-1">{formatBRL(invoice.total_amount)}</p>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                        <span className="text-xs text-slate-400">Protocolo de Autorização</span>
                        <p className="text-sm font-mono font-bold text-emerald-400 mt-2">
                            {invoice.protocol_number || 'Aguardando autorização'}
                        </p>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                        <span className="text-xs text-slate-400">Data de Emissão</span>
                        <p className="text-sm font-bold text-slate-200 mt-2">
                            {invoice.issued_at ? new Date(invoice.issued_at).toLocaleString('pt-BR') : '-'}
                        </p>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                        <span className="text-xs text-slate-400">Destinatário</span>
                        <p className="text-sm font-bold text-slate-200 mt-1 truncate">
                            {invoice.partner?.name || 'Cliente'}
                        </p>
                        <p className="text-[11px] text-slate-400 font-mono">
                            {invoice.partner?.document_number}
                        </p>
                    </div>
                </div>

                {/* Motivo de Rejeição SEFAZ (Se Houver) */}
                {invoice.rejection_reason && (
                    <div className="p-4 bg-rose-950/20 border border-rose-800/40 rounded-xl flex items-start space-x-3 text-xs text-rose-300">
                        <AlertCircle className="w-5 h-5 text-rose-400 shrink-0 mt-0.5" />
                        <div>
                            <span className="font-bold text-rose-200 block mb-1">Rejeição Reportada pela SEFAZ:</span>
                            <p>{invoice.rejection_reason}</p>
                        </div>
                    </div>
                )}
            </div>
        </TenantDesktopLayout>
    );
}
