import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { FileText, ShieldCheck, CreditCard } from 'lucide-react';

export default function FiscalSubNav() {
    const { url } = usePage();

    const links = [
        { 
            label: 'Notas Fiscais (NF-e / NFS-e)', 
            href: '/app/fiscal/invoices', 
            icon: FileText, 
            check: (u: string) => u.startsWith('/app/fiscal/invoices') || u === '/app/fiscal' 
        },
        { 
            label: 'Parâmetros Fiscais & Certificado A1', 
            href: '/app/fiscal/settings/tax', 
            icon: ShieldCheck, 
            check: (u: string) => u.startsWith('/app/fiscal/settings/tax') 
        },
        { 
            label: 'Gateways de Pagamento & Webhooks', 
            href: '/app/fiscal/settings/gateways', 
            icon: CreditCard, 
            check: (u: string) => u.startsWith('/app/fiscal/settings/gateways') 
        },
    ];

    return (
        <div className="flex items-center space-x-2 border-b border-slate-800 pb-3 mb-6 overflow-x-auto">
            {links.map((link) => {
                const Icon = link.icon;
                const isActive = link.check(url);

                return (
                    <Link
                        key={link.label}
                        href={link.href}
                        className={`flex items-center space-x-2 px-3.5 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition ${
                            isActive
                                ? 'bg-indigo-600/15 text-indigo-400 border border-indigo-500/30 shadow-sm'
                                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                        }`}
                    >
                        <Icon className="w-4 h-4" />
                        <span>{link.label}</span>
                    </Link>
                );
            })}
        </div>
    );
}
