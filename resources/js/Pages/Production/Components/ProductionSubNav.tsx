import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Factory, Cpu, Building2, Smartphone } from 'lucide-react';

export default function ProductionSubNav() {
    const { url } = usePage();

    const links = [
        { label: 'Ordens de Produção (OPs)', href: '/app/production/orders', icon: Factory },
        { label: 'Fichas Técnicas (BOM)', href: '/app/production/boms', icon: Cpu },
        { label: 'Centros de Trabalho', href: '/app/production/work-centers', icon: Building2 },
        { label: 'Chão de Fábrica Touch (PWA)', href: '/app/production/shop-floor', icon: Smartphone },
    ];

    return (
        <div className="flex items-center space-x-2 border-b border-slate-800 pb-3 mb-6 overflow-x-auto">
            {links.map((link) => {
                const Icon = link.icon;
                const isActive = url.startsWith(link.href);

                return (
                    <Link
                        key={link.href}
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
