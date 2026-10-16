import React from 'react';
import { Head } from '@inertiajs/react';

interface Props {
    title?: string;
}

export default function Welcome({ title = 'SGI v2.0 Industrial' }: Props) {
    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center p-6">
            <Head title="Bem-vindo ao SGI v2.0" />
            <div className="max-w-xl text-center space-y-4">
                <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    SGI v2.0 • ERP & CRM Industrial Multi-Tenant
                </span>
                <h1 className="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    {title}
                </h1>
                <p className="text-slate-400 text-sm sm:text-base leading-relaxed">
                    Arquitetura Multi-Tenant isolada com Row-Level Security no PostgreSQL, cofre criptográfico AES-256-GCM com HKDF e controle de acesso granular.
                </p>
            </div>
        </div>
    );
}
