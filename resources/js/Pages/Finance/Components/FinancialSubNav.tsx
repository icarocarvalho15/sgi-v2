import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { TrendingUp, TrendingDown, Landmark, Activity, FileSpreadsheet } from 'lucide-react';

export default function FinancialSubNav() {
    const { url } = usePage();

    const links = [
        { label: 'Contas a Receber', href: '/app/finance/entries?entry_type=RECEIVABLE', icon: TrendingUp, check: (u: string) => u.includes('entry_type=RECEIVABLE') || (u.startsWith('/app/finance/entries') && !u.includes('entry_type=PAYABLE')) },
        { label: 'Contas a Pagar', href: '/app/finance/entries?entry_type=PAYABLE', icon: TrendingDown, check: (u: string) => u.includes('entry_type=PAYABLE') },
        { label: 'Contas & Centros de Custo', href: '/app/finance/accounts', icon: Landmark, check: (u: string) => u.startsWith('/app/finance/accounts') },
        { label: 'Fluxo de Caixa', href: '/app/finance/reports/cash-flow', icon: Activity, check: (u: string) => u.startsWith('/app/finance/reports/cash-flow') },
        { label: 'DRE Gerencial', href: '/app/finance/reports/dre', icon: FileSpreadsheet, check: (u: string) => u.startsWith('/app/finance/reports/dre') },
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
