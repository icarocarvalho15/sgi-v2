import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Printer, Lock, Mail, ShieldAlert, ArrowRight, Building2, UserCheck } from 'lucide-react';

interface TenantOption {
    id: string;
    name: string;
    subdomain: string;
}

interface Props {
    tenants: TenantOption[];
}

export default function Login({ tenants }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login');
    };

    const fillDemoTenant = () => {
        setData({
            email: 'operador@express.com',
            password: 'Operador123!',
            remember: true,
        });
    };

    const fillSuperAdmin = () => {
        setData({
            email: 'admin@sgi-erp.com',
            password: 'Admin1234!',
            remember: true,
        });
    };

    return (
        <div className="min-h-screen bg-slate-950 flex flex-col justify-center py-12 sm:px-6 lg:px-8 text-slate-100 selection:bg-indigo-500 selection:text-white">
            <Head title="Acesso ao SGI v2.0 - ERP Gráficas e Brindes" />

            <div className="sm:mx-auto sm:w-full sm:max-w-md text-center space-y-3">
                <div className="inline-flex w-14 h-14 rounded-2xl bg-indigo-600 items-center justify-center font-bold text-white shadow-xl shadow-indigo-600/30">
                    <Printer className="w-8 h-8" />
                </div>
                <h2 className="text-2xl font-bold tracking-tight text-white">
                    SGI v2.0
                </h2>
                <p className="text-sm text-indigo-400 font-medium">
                    ERP & CRM para Gráficas e Brindes Personalizados
                </p>
            </div>

            <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div className="bg-slate-900 py-8 px-6 shadow-2xl border border-slate-800 rounded-2xl sm:px-10 space-y-6">
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="block text-xs font-medium text-slate-300 mb-1">
                                E-mail de Acesso
                            </label>
                            <div className="relative">
                                <Mail className="w-4 h-4 absolute left-3 top-3 text-slate-500" />
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="seu.email@grafica.com.br"
                                    className="w-full pl-9 pr-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                    required
                                />
                            </div>
                            {errors.email && <p className="text-xs text-rose-400 mt-1">{errors.email}</p>}
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-300 mb-1">
                                Senha Secreta
                            </label>
                            <div className="relative">
                                <Lock className="w-4 h-4 absolute left-3 top-3 text-slate-500" />
                                <input
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="••••••••••••"
                                    className="w-full pl-9 pr-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                    required
                                />
                            </div>
                            {errors.password && <p className="text-xs text-rose-400 mt-1">{errors.password}</p>}
                        </div>

                        <div className="flex items-center justify-between text-xs">
                            <label className="flex items-center gap-2 cursor-pointer text-slate-400 hover:text-slate-200">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-0"
                                />
                                Lembrar meu acesso
                            </label>
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full py-2.5 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm transition-all shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-2"
                        >
                            {processing ? 'Autenticando...' : 'Acessar Sistema'}
                            <ArrowRight className="w-4 h-4" />
                        </button>
                    </form>

                    {/* Atalhos para Testes Rápidos */}
                    <div className="pt-4 border-t border-slate-800 space-y-2">
                        <span className="text-[11px] uppercase font-semibold text-slate-500 tracking-wider block text-center">
                            Atalhos de Acesso Rápido para Demonstração
                        </span>

                        <div className="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                onClick={fillDemoTenant}
                                className="py-2 px-3 rounded-lg bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 text-xs font-medium flex items-center justify-center gap-1.5 transition-colors"
                            >
                                <Building2 className="w-3.5 h-3.5 text-indigo-400" />
                                Gráfica Demo
                            </button>

                            <button
                                type="button"
                                onClick={fillSuperAdmin}
                                className="py-2 px-3 rounded-lg bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 text-xs font-medium flex items-center justify-center gap-1.5 transition-colors"
                            >
                                <ShieldAlert className="w-3.5 h-3.5 text-amber-400" />
                                Landlord (SaaS)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
