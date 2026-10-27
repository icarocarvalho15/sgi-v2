import React, { useRef } from 'react';
import { Head, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import FiscalSubNav from '@/Pages/Fiscal/Components/FiscalSubNav';
import { 
    ShieldCheck, 
    FileKey, 
    Lock, 
    Upload, 
    CheckCircle2, 
    AlertTriangle, 
    Building2,
    Save
} from 'lucide-react';

interface TenantTaxSetting {
    id: string;
    environment: 'HOMOLOGATION' | 'PRODUCTION';
    tax_regime: 'SIMPLES_NACIONAL' | 'LUCRO_PRESUMIDO' | 'LUCRO_REAL';
    state_registration?: string;
    municipal_registration?: string;
    nfe_series: number;
    next_nfe_number: number;
    default_cfop: string;
    default_csosn?: string;
    default_cst?: string;
    default_iss_rate: string;
    certificate_path?: string;
    certificate_expires_at?: string;
}

interface Props {
    taxSetting: TenantTaxSetting;
    hasCertificate: boolean;
}

export default function TaxSettings({ taxSetting, hasCertificate }: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors } = useForm({
        environment: taxSetting.environment || 'HOMOLOGATION',
        tax_regime: taxSetting.tax_regime || 'SIMPLES_NACIONAL',
        state_registration: taxSetting.state_registration || '',
        municipal_registration: taxSetting.municipal_registration || '',
        nfe_series: taxSetting.nfe_series || 1,
        next_nfe_number: taxSetting.next_nfe_number || 1,
        default_cfop: taxSetting.default_cfop || '5101',
        default_csosn: taxSetting.default_csosn || '102',
        default_cst: taxSetting.default_cst || '',
        default_iss_rate: taxSetting.default_iss_rate || '0.00',
        certificate_file: null as File | null,
        certificate_password: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/app/fiscal/settings/tax', {
            forceFormData: true,
        });
    };

    return (
        <TenantDesktopLayout title="Parâmetros Fiscais & Certificado Digital A1">
            <Head title="Parâmetros Fiscais - SGI" />

            <div className="p-6 max-w-5xl mx-auto space-y-6">
                <FiscalSubNav />

                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                        <div className="p-2.5 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-xl">
                            <ShieldCheck className="w-5 h-5" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-white tracking-tight">
                                Parâmetros Tributários & Certificado A1
                            </h1>
                            <p className="text-xs text-slate-400">
                                Armazenamento seguro de chaves com AES-256-GCM (VaultEncryption) e parâmetros SEFAZ.
                            </p>
                        </div>
                    </div>

                    <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${
                        data.environment === 'PRODUCTION'
                            ? 'bg-rose-500/15 text-rose-400 border border-rose-500/30'
                            : 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'
                    }`}>
                        {data.environment === 'PRODUCTION' ? 'Ambiente: Produção' : 'Ambiente: Homologação'}
                    </span>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Card Certificado A1 */}
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-4 shadow-sm">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-4">
                            <div className="flex items-center space-x-2.5">
                                <FileKey className="w-5 h-5 text-indigo-400" />
                                <h2 className="text-sm font-bold text-white">Cofre de Certificado Digital A1 (.pfx)</h2>
                            </div>
                            {hasCertificate ? (
                                <span className="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">
                                    <CheckCircle2 className="w-3.5 h-3.5" />
                                    <span>Certificado Instalado no Cofre</span>
                                </span>
                            ) : (
                                <span className="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-500/15 text-amber-400 border border-amber-500/20">
                                    <AlertTriangle className="w-3.5 h-3.5" />
                                    <span>Nenhum Certificado Anexado</span>
                                </span>
                            )}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    Arquivo do Certificado Digital (.pfx / .p12)
                                </label>
                                <input
                                    type="file"
                                    ref={fileInputRef}
                                    accept=".pfx,.p12"
                                    onChange={(e) => setData('certificate_file', e.target.files?.[0] || null)}
                                    className="w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer"
                                />
                                {errors.certificate_file && (
                                    <p className="text-[11px] text-rose-400 mt-1">{errors.certificate_file}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    Senha do Certificado A1
                                </label>
                                <div className="relative">
                                    <input
                                        type="password"
                                        placeholder={hasCertificate ? '••••••••••••' : 'Digite a senha do certificado'}
                                        value={data.certificate_password}
                                        onChange={(e) => setData('certificate_password', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 pr-9"
                                    />
                                    <Lock className="w-4 h-4 text-slate-400 absolute right-3 top-2.5" />
                                </div>
                                <span className="text-[10px] text-slate-400">
                                    A senha é protegida pelo Vault com chave HKDF única do Tenant.
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Card Parâmetros Tributários */}
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-4 shadow-sm">
                        <div className="border-b border-slate-800 pb-4">
                            <h2 className="text-sm font-bold text-white">Parâmetros de Tributação & Emissão</h2>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    Ambiente SEFAZ *
                                </label>
                                <select
                                    value={data.environment}
                                    onChange={(e) => setData('environment', e.target.value as any)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="HOMOLOGATION">Homologação (Sem Valor Fiscal)</option>
                                    <option value="PRODUCTION">Produção (SEFAZ Oficial)</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    Regime Tributário da Empresa *
                                </label>
                                <select
                                    value={data.tax_regime}
                                    onChange={(e) => setData('tax_regime', e.target.value as any)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="SIMPLES_NACIONAL">Simples Nacional (ME/EPP)</option>
                                    <option value="LUCRO_PRESUMIDO">Lucro Presumido</option>
                                    <option value="LUCRO_REAL">Lucro Real</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    Inscrição Estadual (IE)
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ex: 111.222.333.444"
                                    value={data.state_registration}
                                    onChange={(e) => setData('state_registration', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    Série da NF-e *
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    value={data.nfe_series}
                                    onChange={(e) => setData('nfe_series', parseInt(e.target.value) || 1)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-bold"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    Próximo Número de NF-e *
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    value={data.next_nfe_number}
                                    onChange={(e) => setData('next_nfe_number', parseInt(e.target.value) || 1)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-bold"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    CFOP Padrão de Venda *
                                </label>
                                <input
                                    type="text"
                                    placeholder="5101"
                                    value={data.default_cfop}
                                    onChange={(e) => setData('default_cfop', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    CSOSN Padrão (Simples)
                                </label>
                                <input
                                    type="text"
                                    placeholder="102"
                                    value={data.default_csosn}
                                    onChange={(e) => setData('default_csosn', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    CST Padrão (Regime Normal)
                                </label>
                                <input
                                    type="text"
                                    placeholder="00"
                                    value={data.default_cst}
                                    onChange={(e) => setData('default_cst', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-300 mb-1">
                                    Alíquota ISS Padrão (%)
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value={data.default_iss_rate}
                                    onChange={(e) => setData('default_iss_rate', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-end">
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex items-center space-x-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm transition disabled:opacity-50"
                        >
                            <Save className="w-4 h-4" />
                            <span>{processing ? 'Salvando no Cofre...' : 'Salvar Parâmetros Fiscais'}</span>
                        </button>
                    </div>
                </form>
            </div>
        </TenantDesktopLayout>
    );
}
