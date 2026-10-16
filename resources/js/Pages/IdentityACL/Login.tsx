import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Shield, KeyRound, Mail, Lock } from 'lucide-react';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login');
    };

    const fillCredentials = (email: string) => {
        setData({
            email,
            password: 'password',
            remember: true,
        });
    };

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center p-6 font-sans">
            <Head title="Acesso ao Sistema - SGI v2.0" />

            <div className="w-full max-w-md space-y-6">
                <div className="text-center space-y-2">
                    <div className="inline-flex w-12 h-12 rounded-2xl bg-indigo-600 items-center justify-center font-black text-xl text-white shadow-lg shadow-indigo-600/30">
                        SGI
                    </div>
                    <h1 className="text-2xl font-bold tracking-tight text-white">SGI v2.0 Industrial</h1>
                    <p className="text-xs text-slate-400">
                        Sistema Integrado de Gestão ERP & CRM Multi-Tenant
                    </p>
                </div>

                <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
                    <form onSubmit={handleSubmit} className="space-y-4 text-xs">
                        <div>
                            <label className="block font-semibold text-slate-300 mb-1">E-mail Corporativo</label>
                            <div className="relative">
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    placeholder="seu.email@empresa.com.br"
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg pl-9 pr-3 py-2.5 text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"
                                />
                                <Mail className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                            </div>
                            {errors.email && <p className="text-rose-400 text-[11px] mt-1">{errors.email}</p>}
                        </div>

                        <div>
                            <label className="block font-semibold text-slate-300 mb-1">Senha de Acesso</label>
                            <div className="relative">
                                <input
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
                                    placeholder="••••••••"
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg pl-9 pr-3 py-2.5 text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"
                                />
                                <Lock className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                            </div>
                            {errors.password && <p className="text-rose-400 text-[11px] mt-1">{errors.password}</p>}
                        </div>

                        <div className="flex items-center justify-between pt-1">
                            <label className="flex items-center space-x-2 text-slate-400 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="rounded bg-slate-950 border-slate-700 text-indigo-600 focus:ring-0"
                                />
                                <span>Lembrar meu acesso</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2.5 rounded-lg transition shadow-md shadow-indigo-600/25 disabled:opacity-50 text-sm mt-2"
                        >
                            {processing ? 'Autenticando...' : 'Entrar no Sistema'}
                        </button>
                    </form>

                    {/* Atalhos de Demonstração (Seeder) */}
                    <div className="mt-6 pt-5 border-t border-slate-800 text-[11px] text-slate-400 space-y-2">
                        <p className="font-semibold text-slate-300">Contas de demonstração:</p>
                        <div className="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                onClick={() => fillCredentials('admin@sgi.com.br')}
                                className="p-2 rounded bg-slate-800/60 hover:bg-slate-800 text-left border border-slate-700/60 text-slate-300 transition"
                            >
                                <span className="font-bold text-indigo-400 block">Landlord (SaaS)</span>
                                admin@sgi.com.br
                            </button>
                            <button
                                type="button"
                                onClick={() => fillCredentials('alfa.admin@metalurgicaalfa.com.br')}
                                className="p-2 rounded bg-slate-800/60 hover:bg-slate-800 text-left border border-slate-700/60 text-slate-300 transition"
                            >
                                <span className="font-bold text-emerald-400 block">Tenant (Alfa)</span>
                                alfa.admin@...
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
