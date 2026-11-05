import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../../../Layouts/AppLayout';
import { 
    Settings, 
    ShieldCheck, 
    FileKey, 
    CreditCard, 
    CheckCircle2, 
    AlertTriangle,
    Save,
    Lock
} from 'lucide-react';

interface FiscalSettings {
    cnpj: string;
    state_registration?: string;
    municipal_registration?: string;
    tax_regime: string;
    environment: string;
    default_ncm?: string;
    default_cfop?: string;
    certificate_expires_at?: string;
}

interface GatewayConfig {
    id: string;
    gateway_name: string;
    is_active: boolean;
    mode: string;
}

interface Props {
    fiscalSettings: FiscalSettings | null;
    gateways: GatewayConfig[];
    hasCertificate: boolean;
}

export default function FiscalSettingsIndex({ fiscalSettings, gateways, hasCertificate }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        cnpj: fiscalSettings?.cnpj || '',
        state_registration: fiscalSettings?.state_registration || '',
        municipal_registration: fiscalSettings?.municipal_registration || '',
        tax_regime: fiscalSettings?.tax_regime || 'simples_nacional',
        environment: fiscalSettings?.environment || 'sandbox',
        default_ncm: fiscalSettings?.default_ncm || '49111090',
        default_cfop: fiscalSettings?.default_cfop || '5101',
        certificate_file: null as File | null,
        certificate_password: '',
        gateway_name: 'asaas',
        gateway_api_key: '',
        gateway_webhook_secret: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/settings/fiscal', {
            forceFormData: true,
        });
    };

    return (
        <AppLayout title="Configurações Fiscais & Gateways">
            <Head title="Configurações Fiscais - SGI v2.0" />

            <div className="max-w-4xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                                <Settings className="w-7 h-7 text-indigo-400" />
                                Configurações Fiscais & Meios de Pagamento
                            </h1>
                            <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[11px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <ShieldCheck className="w-3.5 h-3.5" />
                                Vault AES-256-GCM
                            </span>
                        </div>
                        <p className="text-sm text-slate-400 mt-1">
                            Parâmetros tributários para emissão de NFe/NFSe e cofre blindado para Certificado Digital A1 e chaves de API.
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Fiscal Data Card */}
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm space-y-4">
                        <h2 className="text-base font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                            <ShieldCheck className="w-5 h-5 text-indigo-400" />
                            Dados Fiscais da Empresa
                        </h2>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    CNPJ da Gráfica / Indústria *
                                </label>
                                <input
                                    type="text"
                                    placeholder="00.000.000/0001-00"
                                    value={data.cnpj}
                                    onChange={(e) => setData('cnpj', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    required
                                />
                                {errors.cnpj && <p className="text-xs text-rose-400 mt-1">{errors.cnpj}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Inscrição Estadual (IE)
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ex: 123.456.789.000"
                                    value={data.state_registration}
                                    onChange={(e) => setData('state_registration', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Inscrição Municipal (IM)
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ex: 98765432"
                                    value={data.municipal_registration}
                                    onChange={(e) => setData('municipal_registration', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Regime Tributário *
                                </label>
                                <select
                                    value={data.tax_regime}
                                    onChange={(e) => setData('tax_regime', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="simples_nacional">Simples Nacional</option>
                                    <option value="lucro_presumido">Lucro Presumido</option>
                                    <option value="lucro_real">Lucro Real</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Ambiente de Emissão *
                                </label>
                                <select
                                    value={data.environment}
                                    onChange={(e) => setData('environment', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="sandbox">Homologação / Testes</option>
                                    <option value="production">Produção Oficial SEFAZ</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    NCM Padrão (Impressos)
                                </label>
                                <input
                                    type="text"
                                    value={data.default_ncm}
                                    onChange={(e) => setData('default_ncm', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    CFOP Padrão (Saída)
                                </label>
                                <input
                                    type="text"
                                    value={data.default_cfop}
                                    onChange={(e) => setData('default_cfop', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Certificate A1 Vault Card */}
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm space-y-4">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h2 className="text-base font-bold text-white flex items-center gap-2">
                                <FileKey className="w-5 h-5 text-amber-400" />
                                Certificado Digital A1 (.pfx)
                            </h2>
                            {hasCertificate ? (
                                <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-medium">
                                    <CheckCircle2 className="w-3.5 h-3.5" />
                                    Certificado Instalado no Cofre
                                </span>
                            ) : (
                                <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs bg-amber-500/10 text-amber-400 border border-amber-500/20 font-medium">
                                    <AlertTriangle className="w-3.5 h-3.5" />
                                    Nenhum Certificado Configurado
                                </span>
                            )}
                        </div>

                        <div className="bg-slate-950/60 p-3 rounded-lg border border-slate-800 text-xs text-slate-400 flex items-start gap-2.5">
                            <Lock className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                            <span>
                                O arquivo binário `.pfx` e a senha são criptografados através do cofre <strong>VaultEncryption (AES-256-GCM)</strong> com chave derivada exclusivamente para sua empresa via HKDF.
                            </span>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Arquivo do Certificado (.pfx / .p12)
                                </label>
                                <input
                                    type="file"
                                    accept=".pfx,.p12"
                                    onChange={(e) => setData('certificate_file', e.target.files ? e.target.files[0] : null)}
                                    className="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700 cursor-pointer"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Senha do Certificado Digital
                                </label>
                                <input
                                    type="password"
                                    placeholder="••••••••••••"
                                    value={data.certificate_password}
                                    onChange={(e) => setData('certificate_password', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Payment Gateways Card */}
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm space-y-4">
                        <h2 className="text-base font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                            <CreditCard className="w-5 h-5 text-emerald-400" />
                            Gateways de Pagamento (Asaas, Mercado Pago, Stripe)
                        </h2>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Gateway Ativo
                                </label>
                                <select
                                    value={data.gateway_name}
                                    onChange={(e) => setData('gateway_name', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="asaas">Asaas (Pix & Boleto Nativo)</option>
                                    <option value="mercadopago">Mercado Pago</option>
                                    <option value="stripe">Stripe</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Chave de API / Access Token
                                </label>
                                <input
                                    type="password"
                                    placeholder="$aact_YTU5YTE0M2M..."
                                    value={data.gateway_api_key}
                                    onChange={(e) => setData('gateway_api_key', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Webhook Secret / Token HMAC
                                </label>
                                <input
                                    type="password"
                                    placeholder="whsec_..."
                                    value={data.gateway_webhook_secret}
                                    onChange={(e) => setData('gateway_webhook_secret', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3 pt-3">
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-sm font-medium transition-colors shadow-lg shadow-indigo-600/20"
                        >
                            <Save className="w-4 h-4" />
                            {processing ? 'Salvando no Cofre...' : 'Salvar Configurações'}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
