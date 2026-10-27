import React, { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import FiscalSubNav from '@/Pages/Fiscal/Components/FiscalSubNav';
import { PageProps } from '@/types';
import { 
    CreditCard, 
    Lock, 
    Key, 
    Webhook, 
    Copy, 
    Check, 
    CheckCircle2, 
    AlertCircle, 
    Save,
    QrCode,
    FileText
} from 'lucide-react';

interface PaymentGatewaySetting {
    id: string;
    gateway: 'ASAAS' | 'STRIPE' | 'MERCADOPAGO';
    is_sandbox: boolean;
    is_active: boolean;
    is_default: boolean;
}

interface Props {
    gateways: PaymentGatewaySetting[];
}

export default function GatewaysSettings({ gateways }: Props) {
    const { props } = usePage<PageProps>();
    const tenantSubdomain = props.auth.tenant?.subdomain || 'app';
    const [selectedGateway, setSelectedGateway] = useState<'ASAAS' | 'STRIPE' | 'MERCADOPAGO'>('ASAAS');
    const [copiedUrl, setCopiedUrl] = useState(false);

    const currentSetting = gateways.find((g) => g.gateway === selectedGateway);

    const { data, setData, post, processing, errors, reset } = useForm({
        gateway: selectedGateway,
        api_key: '',
        webhook_secret: '',
        is_sandbox: currentSetting ? currentSetting.is_sandbox : true,
        is_active: currentSetting ? currentSetting.is_active : true,
        is_default: currentSetting ? currentSetting.is_default : false,
    });

    const handleSelectGateway = (gw: 'ASAAS' | 'STRIPE' | 'MERCADOPAGO') => {
        setSelectedGateway(gw);
        const match = gateways.find((g) => g.gateway === gw);
        setData({
            gateway: gw,
            api_key: '',
            webhook_secret: '',
            is_sandbox: match ? match.is_sandbox : true,
            is_active: match ? match.is_active : true,
            is_default: match ? match.is_default : false,
        });
    };

    const webhookEndpoint = `${window.location.origin}/api/webhooks/gateway/${selectedGateway.toLowerCase()}`;

    const handleCopyWebhook = () => {
        navigator.clipboard.writeText(webhookEndpoint);
        setCopiedUrl(true);
        setTimeout(() => setCopiedUrl(false), 2000);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/app/fiscal/settings/gateways', {
            onSuccess: () => {
                reset('api_key', 'webhook_secret');
            },
        });
    };

    return (
        <TenantDesktopLayout title="Gateways de Pagamento & Cobrança">
            <Head title="Gateways & Webhooks - SGI" />

            <div className="p-6 max-w-5xl mx-auto space-y-6">
                <FiscalSubNav />

                {/* Header */}
                <div className="flex items-center space-x-3">
                    <div className="p-2.5 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-xl">
                        <CreditCard className="w-5 h-5" />
                    </div>
                    <div>
                        <h1 className="text-xl font-bold text-white tracking-tight">
                            Gateways de Pagamento & Webhooks
                        </h1>
                        <p className="text-xs text-slate-400">
                            Integrações com Asaas, Stripe e Mercado Pago para geração de Pix Dinâmico, Boletos e Cartão com baixa automática.
                        </p>
                    </div>
                </div>

                {/* Seletor de Gateways */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {[
                        { key: 'ASAAS', name: 'Asaas Pagamentos', desc: 'Pix Dinâmico & Boletos com QR Code' },
                        { key: 'STRIPE', name: 'Stripe Global', desc: 'Cartão de Crédito Internacional & Pix' },
                        { key: 'MERCADOPAGO', name: 'Mercado Pago', desc: 'Checkout Transparente & Pix Instantâneo' },
                    ].map((gw) => {
                        const isConfigured = gateways.some((g) => g.gateway === gw.key && g.is_active);
                        const isSelected = selectedGateway === gw.key;

                        return (
                            <button
                                key={gw.key}
                                type="button"
                                onClick={() => handleSelectGateway(gw.key as any)}
                                className={`text-left p-4 rounded-xl border transition ${
                                    isSelected
                                        ? 'bg-slate-900 border-indigo-500 shadow-md ring-1 ring-indigo-500'
                                        : 'bg-slate-900/60 border-slate-800 hover:border-slate-700'
                                }`}
                            >
                                <div className="flex items-center justify-between mb-2">
                                    <h3 className="font-bold text-white text-sm">{gw.name}</h3>
                                    {isConfigured ? (
                                        <span className="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">
                                            Ativo
                                        </span>
                                    ) : (
                                        <span className="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-800 text-slate-400">
                                            Não Configurado
                                        </span>
                                    )}
                                </div>
                                <p className="text-[11px] text-slate-400">{gw.desc}</p>
                            </button>
                        );
                    })}
                </div>

                {/* Formulário de Configuração do Gateway Selecionado */}
                <form onSubmit={handleSubmit} className="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-6 shadow-sm">
                    <div className="border-b border-slate-800 pb-4 flex items-center justify-between">
                        <div>
                            <h2 className="text-sm font-bold text-white">Configurações: {selectedGateway}</h2>
                            <p className="text-xs text-slate-400">
                                Todas as chaves e tokens de webhook são criptografados com AES-256-GCM via VaultEncryption.
                            </p>
                        </div>
                    </div>

                    {/* URL de Webhook para colar no painel do gateway */}
                    <div className="p-4 bg-slate-950 border border-slate-800 rounded-xl space-y-2">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold text-slate-300 flex items-center space-x-1.5">
                                <Webhook className="w-4 h-4 text-indigo-400" />
                                <span>URL de Webhook Exclusiva do Tenant (Endpoint SEFAZ/Gateway):</span>
                            </span>
                            <button
                                type="button"
                                onClick={handleCopyWebhook}
                                className="flex items-center space-x-1 text-xs text-indigo-400 hover:text-indigo-300 transition"
                            >
                                {copiedUrl ? (
                                    <>
                                        <Check className="w-3.5 h-3.5 text-emerald-400" />
                                        <span className="text-emerald-400">Copiado!</span>
                                    </>
                                ) : (
                                    <>
                                        <Copy className="w-3.5 h-3.5" />
                                        <span>Copiar URL</span>
                                    </>
                                )}
                            </button>
                        </div>
                        <p className="font-mono text-xs text-slate-400 bg-slate-900/80 p-2.5 rounded border border-slate-800/60 select-all break-all">
                            {webhookEndpoint}
                        </p>
                        <span className="text-[10px] text-slate-400 block">
                            Cadastre esta URL no painel do {selectedGateway} para receber notificações automáticas de pagamento com validação HMAC.
                        </span>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="md:col-span-2">
                            <label className="block text-xs font-medium text-slate-300 mb-1">
                                Chave de API / Access Token / Secret Key *
                            </label>
                            <input
                                type="password"
                                required
                                placeholder="Insira a chave de API fornecida pelo gateway"
                                value={data.api_key}
                                onChange={(e) => setData('api_key', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                            {errors.api_key && (
                                <p className="text-[11px] text-rose-400 mt-1">{errors.api_key}</p>
                            )}
                        </div>

                        <div className="md:col-span-2">
                            <label className="block text-xs font-medium text-slate-300 mb-1">
                                Segredo de Assinatura do Webhook (Webhook Secret / HMAC Key)
                            </label>
                            <input
                                type="password"
                                placeholder="Segredo usado para validar a autenticidade das requisições HMAC"
                                value={data.webhook_secret}
                                onChange={(e) => setData('webhook_secret', e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                            {errors.webhook_secret && (
                                <p className="text-[11px] text-rose-400 mt-1">{errors.webhook_secret}</p>
                            )}
                        </div>

                        <div className="flex items-center space-x-2 pt-2">
                            <input
                                type="checkbox"
                                id="is_sandbox"
                                checked={data.is_sandbox}
                                onChange={(e) => setData('is_sandbox', e.target.checked)}
                                className="rounded border-slate-800 bg-slate-950 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label htmlFor="is_sandbox" className="text-xs text-slate-300 cursor-pointer">
                                Modo Sandbox / Testes Ativado
                            </label>
                        </div>

                        <div className="flex items-center space-x-2 pt-2">
                            <input
                                type="checkbox"
                                id="is_active"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                                className="rounded border-slate-800 bg-slate-950 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label htmlFor="is_active" className="text-xs text-slate-300 cursor-pointer">
                                Gateway Habilitado para Cobrança
                            </label>
                        </div>
                    </div>

                    <div className="pt-4 border-t border-slate-800 flex justify-end">
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex items-center space-x-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm transition disabled:opacity-50"
                        >
                            <Save className="w-4 h-4" />
                            <span>{processing ? 'Salvando...' : `Salvar Credenciais do ${selectedGateway}`}</span>
                        </button>
                    </div>
                </form>
            </div>
        </TenantDesktopLayout>
    );
}
