import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import ShopFloorMobileLayout from '@/Layouts/ShopFloorMobileLayout';
import { PlanFeatures } from '@/types';
import { 
    Factory, 
    Boxes, 
    Cpu, 
    Users, 
    Coins, 
    ReceiptText, 
    Lock, 
    Smartphone, 
    Monitor,
    Sparkles,
    CheckCircle2
} from 'lucide-react';

interface Props {
    tenant: {
        id: string;
        name: string;
        trade_name?: string | null;
        subdomain: string;
        status: string;
        active_members_count: number;
    };
    plan: {
        name: string;
        slug: string;
        max_users: number;
        features: PlanFeatures;
    };
    userRole: string;
}

export default function TenantDashboard({ tenant, plan, userRole }: Props) {
    const [viewMode, setViewMode] = useState<'desktop' | 'mobile'>('desktop');

    const modules = [
        {
            key: 'crm',
            name: 'CRM & Orçamentos',
            description: 'Funil de vendas, orçamentos sob medida e conversão 1-clique em pedido de venda.',
            icon: Users,
            requiredFeature: 'crm',
            badge: 'Fase 4',
        },
        {
            key: 'inventory',
            name: 'Suprimentos & Kardex',
            description: 'Razão imutável de estoque, controle de lotes, almoxarifados e lock pessimista.',
            icon: Boxes,
            requiredFeature: 'inventory',
            badge: 'Fase 2',
        },
        {
            key: 'engineering',
            name: 'Engenharia (BOM)',
            description: 'Ficha técnica multinível, detecção de dependência circular e rollup de custo.',
            icon: Cpu,
            requiredFeature: 'engineering',
            badge: 'Fase 3',
        },
        {
            key: 'production',
            name: 'Ordens de Produção (OP)',
            description: 'Apontamento em chão de fábrica, máquina de estados e integração com estoque.',
            icon: Factory,
            requiredFeature: 'production',
            badge: 'Fase 3',
        },
        {
            key: 'finance',
            name: 'Módulo Financeiro',
            description: 'Contas a pagar/receber, baixas parciais, plano de contas e fluxo de caixa.',
            icon: Coins,
            requiredFeature: 'finance',
            badge: 'Fase 5',
        },
        {
            key: 'fiscal',
            name: 'Integrações Fiscais (NFe)',
            description: 'Emissão assíncrona com certificado A1 protegido no cofre criptográfico AES-GCM.',
            icon: ReceiptText,
            requiredFeature: 'nfe_enabled',
            badge: 'Fase 6',
        },
    ];

    // Conteúdo da Visão Mobile (Chão de Fábrica Touch)
    if (viewMode === 'mobile') {
        return (
            <ShopFloorMobileLayout title="Chão de Fábrica Touch" showBackToDesktop={false}>
                <Head title="Terminal Chão de Fábrica - SGI v2.0" />

                <div className="space-y-4">
                    {/* Botão de Retorno ao Desktop */}
                    <div className="flex justify-end">
                        <button
                            onClick={() => setViewMode('desktop')}
                            className="bg-slate-800 border border-slate-700 text-slate-300 text-xs px-3 py-1.5 rounded-lg flex items-center space-x-1"
                        >
                            <Monitor className="w-3.5 h-3.5" />
                            <span>Alternar para ERP Desktop</span>
                        </button>
                    </div>

                    {/* Card de Ordem de Produção Ativa */}
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-lg">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-mono font-bold text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded">
                                OP #2026-0042
                            </span>
                            <span className="text-[11px] bg-emerald-500/10 text-emerald-400 font-bold px-2 py-0.5 rounded-full">
                                EM ANDAMENTO
                            </span>
                        </div>

                        <div>
                            <h2 className="text-base font-bold text-white">Eixo Usinado CNC 45mm</h2>
                            <p className="text-xs text-slate-400 mt-0.5">Etapa 2/4: Torneamento de Precisão • Torno CNC 01</p>
                        </div>

                        {/* Cronômetro Touch Gigante */}
                        <div className="bg-slate-950 border border-slate-800/80 rounded-xl p-4 text-center">
                            <span className="text-3xl font-black font-mono tracking-wider text-emerald-400">
                                01:24:38
                            </span>
                            <p className="text-[11px] text-slate-400 mt-1">Tempo de Operação Decorrido</p>
                        </div>

                        {/* Botões Touch Grandes (>= 56px de altura) */}
                        <div className="grid grid-cols-2 gap-3 pt-2">
                            <button
                                type="button"
                                className="h-14 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl flex items-center justify-center space-x-2 active:scale-95 transition"
                            >
                                <span className="text-sm">Pausar / Setup</span>
                            </button>

                            <button
                                type="button"
                                className="h-14 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl flex items-center justify-center space-x-2 active:scale-95 transition shadow-lg shadow-emerald-600/30"
                            >
                                <span className="text-sm">Finalizar Etapa</span>
                            </button>
                        </div>
                    </div>

                    {/* Fila de Tarefas da Máquina */}
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-3">
                        <h3 className="text-xs font-bold text-slate-300 uppercase tracking-wider">Próximas OPs na Fila</h3>
                        <div className="p-3 bg-slate-950 rounded-xl border border-slate-800 flex items-center justify-between text-xs">
                            <div>
                                <p className="font-bold text-white">OP #2026-0043</p>
                                <p className="text-slate-400 text-[11px]">Flange de Fixação Industrial (Qtd: 120 un)</p>
                            </div>
                            <span className="text-[10px] text-slate-400 font-mono">Aguardando</span>
                        </div>
                    </div>
                </div>
            </ShopFloorMobileLayout>
        );
    }

    // Conteúdo da Visão Desktop (ERP / CRM)
    return (
        <TenantDesktopLayout title="Visão Geral do ERP">
            <Head title={`ERP Industrial - ${tenant.name}`} />

            <div className="space-y-8">
                {/* Banner de Boas-Vindas & Status */}
                <div className="bg-gradient-to-r from-slate-900 via-slate-900 to-indigo-950/40 border border-slate-800 rounded-2xl p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-sm">
                    <div className="space-y-2">
                        <div className="flex items-center space-x-2">
                            <span className="bg-indigo-500/20 text-indigo-400 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-indigo-500/30">
                                Papel Ativo: {userRole}
                            </span>
                            <span className="bg-emerald-500/20 text-emerald-400 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-emerald-500/30">
                                Plano: {plan.name}
                            </span>
                        </div>
                        <h1 className="text-2xl sm:text-3xl font-black text-white tracking-tight">
                            {tenant.name}
                        </h1>
                        <p className="text-xs sm:text-sm text-slate-400 max-w-xl leading-relaxed">
                            Ambiente operacional do ERP/CRM isolado com PostgreSQL Row-Level Security nativo e fail-closed.
                        </p>
                    </div>

                    <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                        <button
                            onClick={() => setViewMode('mobile')}
                            className="bg-amber-500 hover:bg-amber-400 text-slate-950 px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-2 shadow-md shadow-amber-500/20"
                        >
                            <Smartphone className="w-4 h-4" />
                            <span>Simular Chão de Fábrica Touch</span>
                        </button>

                        <Link
                            href="/select-tenant"
                            className="bg-slate-800 hover:bg-slate-700 text-slate-200 px-4 py-2.5 rounded-xl text-xs font-semibold transition border border-slate-700 text-center"
                        >
                            Alternar Empresa
                        </Link>
                    </div>
                </div>

                {/* Grid dos Módulos do ERP */}
                <div className="space-y-4">
                    <div>
                        <h2 className="text-lg font-bold text-white tracking-tight">Módulos do Sistema de Gestão Integrada</h2>
                        <p className="text-xs text-slate-400">
                            Acesso condicionado às permissões do seu papel e aos limites do plano contratado ({plan.name}).
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        {modules.map((m) => {
                            const Icon = m.icon;
                            const isAllowed = m.requiredFeature ? Boolean(plan.features[m.requiredFeature]) : true;

                            return (
                                <div
                                    key={m.key}
                                    className={`bg-slate-900 border rounded-xl p-5 flex flex-col justify-between transition ${
                                        isAllowed 
                                            ? 'border-slate-800 hover:border-slate-700 hover:bg-slate-800/40' 
                                            : 'border-slate-800/60 opacity-60 bg-slate-950/40'
                                    }`}
                                >
                                    <div className="space-y-3">
                                        <div className="flex items-center justify-between">
                                            <div className={`w-10 h-10 rounded-lg flex items-center justify-center ${
                                                isAllowed ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20' : 'bg-slate-800 text-slate-400'
                                            }`}>
                                                <Icon className="w-5 h-5" />
                                            </div>

                                            <div className="flex items-center space-x-1.5">
                                                <span className="text-[10px] font-mono text-slate-400 bg-slate-800 px-2 py-0.5 rounded">
                                                    {m.badge}
                                                </span>
                                                {!isAllowed && (
                                                    <span title="Não incluso no plano">
                                                        <Lock className="w-3.5 h-3.5 text-amber-400" />
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        <div>
                                            <h3 className="font-bold text-sm text-white">{m.name}</h3>
                                            <p className="text-xs text-slate-400 mt-1 leading-relaxed">
                                                {m.description}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="pt-4 mt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
                                        {isAllowed ? (
                                            <span className="text-emerald-400 font-semibold flex items-center space-x-1">
                                                <CheckCircle2 className="w-3.5 h-3.5" />
                                                <span>Liberado no seu plano</span>
                                            </span>
                                        ) : (
                                            <span className="text-amber-400 font-medium flex items-center space-x-1">
                                                <Lock className="w-3.5 h-3.5" />
                                                <span>Requer Upgrade</span>
                                            </span>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </TenantDesktopLayout>
    );
}
