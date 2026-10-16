import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Boxes, Warehouse, FileSpreadsheet, PackageCheck } from 'lucide-react';

export default function InventorySubNav() {
    const { url } = usePage();

    const links = [
        { label: 'Catálogo de Itens', href: '/app/inventory/items', icon: Boxes },
        { label: 'Posição & Saldos', href: '/app/inventory/balances', icon: PackageCheck },
        { label: 'Extrato Kardex', href: '/app/inventory/kardex', icon: FileSpreadsheet },
        { label: 'Almoxarifados', href: '/app/inventory/warehouses', icon: Warehouse },
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
