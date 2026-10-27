import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import TenantDesktopLayout from '@/Layouts/TenantDesktopLayout';
import FinancialSubNav from '@/Pages/Finance/Components/FinancialSubNav';
import { 
    Landmark, 
    Plus, 
    Wallet, 
    FolderTree, 
    Target, 
    X,
    CheckCircle2, 
    CreditCard,
    DollarSign,
    Layers
} from 'lucide-react';

interface FinancialAccount {
    id: string;
    name: string;
    type: 'CHECKING' | 'INVESTMENT' | 'CASH' | 'SAVINGS' | 'OTHER';
    bank_name?: string;
    agency_number?: string;
    account_number?: string;
    current_balance: string;
    is_default: boolean;
    is_active: boolean;
}

interface ChartOfAccount {
    id: string;
    code: string;
    name: string;
    type: 'REVENUE' | 'EXPENSE' | 'ASSET' | 'LIABILITY' | 'EQUITY';
    parent_id?: string;
    is_active: boolean;
}

interface CostCenter {
    id: string;
    code: string;
    name: string;
    description?: string;
    is_active: boolean;
}

interface Props {
    accounts: FinancialAccount[];
    chartOfAccounts: ChartOfAccount[];
    costCenters: CostCenter[];
}

export default function AccountsIndex({ accounts, chartOfAccounts, costCenters }: Props) {
    const [activeTab, setActiveTab] = useState<'accounts' | 'chart' | 'costCenters'>('accounts');

    // Modals
    const [isAccountModalOpen, setIsAccountModalOpen] = useState(false);
    const [isChartModalOpen, setIsChartModalOpen] = useState(false);
    const [isCostCenterModalOpen, setIsCostCenterModalOpen] = useState(false);

    // Account Form
    const accountForm = useForm({
        name: '',
        type: 'CHECKING',
        bank_name: '',
        agency_number: '',
        account_number: '',
        current_balance: '0.00',
        is_default: false,
    });

    // Chart Form
    const chartForm = useForm({
        code: '',
        name: '',
        type: 'REVENUE',
        parent_id: '',
    });

    // Cost Center Form
    const costCenterForm = useForm({
        code: '',
        name: '',
        description: '',
    });

    const formatBRL = (val: string | number) => {
        const num = typeof val === 'string' ? parseFloat(val) : val;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(num || 0);
    };

    const handleAccountSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        accountForm.post('/app/finance/accounts', {
            onSuccess: () => {
                setIsAccountModalOpen(false);
                accountForm.reset();
            },
        });
    };

    const handleChartSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        chartForm.post('/app/finance/chart-of-accounts', {
            onSuccess: () => {
                setIsChartModalOpen(false);
                chartForm.reset();
            },
        });
    };

    const handleCostCenterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        costCenterForm.post('/app/finance/cost-centers', {
            onSuccess: () => {
                setIsCostCenterModalOpen(false);
                costCenterForm.reset();
            },
        });
    };

    return (
        <TenantDesktopLayout title="Estrutura Contábil & Contas Bancárias">
            <Head title="Estrutura Financeira - SGI" />

            <div className="p-6 max-w-7xl mx-auto space-y-6">
                <FinancialSubNav />

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center space-x-3">
                        <div className="p-2 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-lg">
                            <Landmark className="w-5 h-5" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-white tracking-tight">
                                Estrutura Financeira & Conciliação
                            </h1>
                            <p className="text-xs text-slate-400">
                                Gerencie contas bancárias/caixas, categorias do plano de contas e centros de custo da fábrica.
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center space-x-2">
                        {activeTab === 'accounts' && (
                            <button
                                onClick={() => setIsAccountModalOpen(true)}
                                className="flex items-center space-x-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm transition"
                            >
                                <Plus className="w-4 h-4" />
                                <span>Nova Conta / Caixa</span>
                            </button>
                        )}
                        {activeTab === 'chart' && (
                            <button
                                onClick={() => setIsChartModalOpen(true)}
                                className="flex items-center space-x-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm transition"
                            >
                                <Plus className="w-4 h-4" />
                                <span>Nova Categoria</span>
                            </button>
                        )}
                        {activeTab === 'costCenters' && (
                            <button
                                onClick={() => setIsCostCenterModalOpen(true)}
                                className="flex items-center space-x-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm transition"
                            >
                                <Plus className="w-4 h-4" />
                                <span>Novo Centro de Custo</span>
                            </button>
                        )}
                    </div>
                </div>

                {/* Sub-Tabs de Navegação */}
                <div className="flex space-x-2 border-b border-slate-800">
                    <button
                        onClick={() => setActiveTab('accounts')}
                        className={`flex items-center space-x-2 px-4 py-2.5 text-xs font-semibold border-b-2 transition ${
                            activeTab === 'accounts'
                                ? 'border-indigo-500 text-indigo-400'
                                : 'border-transparent text-slate-400 hover:text-slate-200'
                        }`}
                    >
                        <Wallet className="w-4 h-4" />
                        <span>Contas Bancárias & Caixas ({accounts.length})</span>
                    </button>

                    <button
                        onClick={() => setActiveTab('chart')}
                        className={`flex items-center space-x-2 px-4 py-2.5 text-xs font-semibold border-b-2 transition ${
                            activeTab === 'chart'
                                ? 'border-indigo-500 text-indigo-400'
                                : 'border-transparent text-slate-400 hover:text-slate-200'
                        }`}
                    >
                        <FolderTree className="w-4 h-4" />
                        <span>Plano de Contas ({chartOfAccounts.length})</span>
                    </button>

                    <button
                        onClick={() => setActiveTab('costCenters')}
                        className={`flex items-center space-x-2 px-4 py-2.5 text-xs font-semibold border-b-2 transition ${
                            activeTab === 'costCenters'
                                ? 'border-indigo-500 text-indigo-400'
                                : 'border-transparent text-slate-400 hover:text-slate-200'
                        }`}
                    >
                        <Target className="w-4 h-4" />
                        <span>Centros de Custo ({costCenters.length})</span>
                    </button>
                </div>

                {/* Conteúdo Aba: Contas Bancárias */}
                {activeTab === 'accounts' && (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {accounts.length === 0 ? (
                            <div className="col-span-full py-12 text-center text-slate-400 bg-slate-900 border border-slate-800 rounded-xl">
                                Nenhuma conta financeira cadastrada. Crie uma para começar a registrar baixas.
                            </div>
                        ) : (
                            accounts.map((acc) => (
                                <div key={acc.id} className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4 hover:border-slate-700 transition shadow-sm">
                                    <div className="flex items-start justify-between">
                                        <div className="flex items-center space-x-3">
                                            <div className="p-2.5 bg-indigo-500/10 text-indigo-400 rounded-xl">
                                                <CreditCard className="w-5 h-5" />
                                            </div>
                                            <div>
                                                <h3 className="font-bold text-white text-sm">{acc.name}</h3>
                                                <p className="text-[11px] text-slate-400">
                                                    {acc.bank_name || 'Caixa Físico'} • {acc.type}
                                                </p>
                                            </div>
                                        </div>
                                        {acc.is_default && (
                                            <span className="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">
                                                Principal
                                            </span>
                                        )}
                                    </div>

                                    <div className="p-3 bg-slate-950/80 rounded-lg flex items-center justify-between text-xs font-mono text-slate-400">
                                        <span>Ag: {acc.agency_number || '-'}</span>
                                        <span>Conta: {acc.account_number || '-'}</span>
                                    </div>

                                    <div className="pt-2 border-t border-slate-800 flex items-center justify-between">
                                        <span className="text-xs text-slate-400">Saldo Atual Conciliado:</span>
                                        <span className={`text-base font-bold ${parseFloat(acc.current_balance) >= 0 ? 'text-emerald-400' : 'text-rose-400'}`}>
                                            {formatBRL(acc.current_balance)}
                                        </span>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                )}

                {/* Conteúdo Aba: Plano de Contas */}
                {activeTab === 'chart' && (
                    <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-950/60 text-slate-400 font-semibold border-b border-slate-800 uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3 px-4">Código Estruturado</th>
                                    <th className="py-3 px-4">Nome da Categoria</th>
                                    <th className="py-3 px-4">Tipo Contábil</th>
                                    <th className="py-3 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {chartOfAccounts.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="py-12 text-center text-slate-400">
                                            Nenhuma categoria cadastrada no plano de contas.
                                        </td>
                                    </tr>
                                ) : (
                                    chartOfAccounts.map((item) => (
                                        <tr key={item.id} className="hover:bg-slate-800/40 transition">
                                            <td className="py-3 px-4 font-mono font-bold text-indigo-400">
                                                {item.code}
                                            </td>
                                            <td className="py-3 px-4 font-medium text-white">
                                                {item.name}
                                            </td>
                                            <td className="py-3 px-4">
                                                <span className={`inline-flex px-2 py-0.5 rounded text-[10px] font-semibold ${
                                                    item.type === 'REVENUE' 
                                                        ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/20'
                                                        : 'bg-rose-500/15 text-rose-400 border border-rose-500/20'
                                                }`}>
                                                    {item.type}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-center">
                                                <span className="text-[10px] text-emerald-400 font-semibold">Ativo</span>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Conteúdo Aba: Centros de Custo */}
                {activeTab === 'costCenters' && (
                    <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-950/60 text-slate-400 font-semibold border-b border-slate-800 uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3 px-4">Código</th>
                                    <th className="py-3 px-4">Nome do Centro de Custo</th>
                                    <th className="py-3 px-4">Descrição / Setor Fabril</th>
                                    <th className="py-3 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {costCenters.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="py-12 text-center text-slate-400">
                                            Nenhum centro de custo cadastrado.
                                        </td>
                                    </tr>
                                ) : (
                                    costCenters.map((cc) => (
                                        <tr key={cc.id} className="hover:bg-slate-800/40 transition">
                                            <td className="py-3 px-4 font-mono font-bold text-slate-200">
                                                {cc.code}
                                            </td>
                                            <td className="py-3 px-4 font-semibold text-white">
                                                {cc.name}
                                            </td>
                                            <td className="py-3 px-4 text-slate-400">
                                                {cc.description || '-'}
                                            </td>
                                            <td className="py-3 px-4 text-center">
                                                <span className="text-[10px] text-emerald-400 font-semibold">Ativo</span>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Modal Nova Conta */}
                {isAccountModalOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                        <div className="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-md overflow-hidden shadow-2xl">
                            <div className="p-5 border-b border-slate-800 flex items-center justify-between">
                                <h2 className="text-base font-bold text-white">Cadastrar Conta Bancária / Caixa</h2>
                                <button onClick={() => setIsAccountModalOpen(false)} className="text-slate-400 hover:text-white">
                                    <X className="w-5 h-5" />
                                </button>
                            </div>
                            <form onSubmit={handleAccountSubmit} className="p-6 space-y-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">Nome Identificador *</label>
                                    <input
                                        type="text"
                                        required
                                        placeholder="Ex: Itaú Conta Corrente Indústria"
                                        value={accountForm.data.name}
                                        onChange={(e) => accountForm.setData('name', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">Tipo de Conta *</label>
                                        <select
                                            value={accountForm.data.type}
                                            onChange={(e) => accountForm.setData('type', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        >
                                            <option value="CHECKING">Conta Corrente</option>
                                            <option value="INVESTMENT">Aplicação</option>
                                            <option value="CASH">Caixa Físico / Gaveta</option>
                                            <option value="SAVINGS">Poupança</option>
                                            <option value="OTHER">Outros</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">Banco</label>
                                        <input
                                            type="text"
                                            placeholder="Ex: Banco Itaú"
                                            value={accountForm.data.bank_name}
                                            onChange={(e) => accountForm.setData('bank_name', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">Agência</label>
                                        <input
                                            type="text"
                                            placeholder="0001"
                                            value={accountForm.data.agency_number}
                                            onChange={(e) => accountForm.setData('agency_number', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-slate-300 mb-1">Número da Conta</label>
                                        <input
                                            type="text"
                                            placeholder="12345-6"
                                            value={accountForm.data.account_number}
                                            onChange={(e) => accountForm.setData('account_number', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">Saldo Inicial (R$)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={accountForm.data.current_balance}
                                        onChange={(e) => accountForm.setData('current_balance', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-bold"
                                    />
                                </div>
                                <div className="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                                    <button type="button" onClick={() => setIsAccountModalOpen(false)} className="px-4 py-2 text-xs text-slate-400">Cancelar</button>
                                    <button type="submit" disabled={accountForm.processing} className="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg">Salvar Conta</button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Modal Nova Categoria Plano de Contas */}
                {isChartModalOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                        <div className="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-md overflow-hidden shadow-2xl">
                            <div className="p-5 border-b border-slate-800 flex items-center justify-between">
                                <h2 className="text-base font-bold text-white">Nova Categoria de Plano de Contas</h2>
                                <button onClick={() => setIsChartModalOpen(false)} className="text-slate-400 hover:text-white">
                                    <X className="w-5 h-5" />
                                </button>
                            </div>
                            <form onSubmit={handleChartSubmit} className="p-6 space-y-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">Código Estruturado *</label>
                                    <input
                                        type="text"
                                        required
                                        placeholder="Ex: 3.1.01"
                                        value={chartForm.data.code}
                                        onChange={(e) => chartForm.setData('code', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">Nome da Categoria *</label>
                                    <input
                                        type="text"
                                        required
                                        placeholder="Ex: Receita com Venda de Produtos Acabados"
                                        value={chartForm.data.name}
                                        onChange={(e) => chartForm.setData('name', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">Tipo da Categoria *</label>
                                    <select
                                        value={chartForm.data.type}
                                        onChange={(e) => chartForm.setData('type', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="REVENUE">Receita (Entrada)</option>
                                        <option value="EXPENSE">Despesa (Saída)</option>
                                        <option value="ASSET">Ativo</option>
                                        <option value="LIABILITY">Passivo</option>
                                        <option value="EQUITY">Patrimônio Líquido</option>
                                    </select>
                                </div>
                                <div className="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                                    <button type="button" onClick={() => setIsChartModalOpen(false)} className="px-4 py-2 text-xs text-slate-400">Cancelar</button>
                                    <button type="submit" disabled={chartForm.processing} className="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg">Salvar Categoria</button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Modal Novo Centro de Custo */}
                {isCostCenterModalOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                        <div className="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-md overflow-hidden shadow-2xl">
                            <div className="p-5 border-b border-slate-800 flex items-center justify-between">
                                <h2 className="text-base font-bold text-white">Novo Centro de Custo</h2>
                                <button onClick={() => setIsCostCenterModalOpen(false)} className="text-slate-400 hover:text-white">
                                    <X className="w-5 h-5" />
                                </button>
                            </div>
                            <form onSubmit={handleCostCenterSubmit} className="p-6 space-y-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">Código *</label>
                                    <input
                                        type="text"
                                        required
                                        placeholder="Ex: CC-FAB-01"
                                        value={costCenterForm.data.code}
                                        onChange={(e) => costCenterForm.setData('code', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">Nome *</label>
                                    <input
                                        type="text"
                                        required
                                        placeholder="Ex: Usinagem & Corte CNC"
                                        value={costCenterForm.data.name}
                                        onChange={(e) => costCenterForm.setData('name', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-slate-300 mb-1">Descrição</label>
                                    <textarea
                                        rows={2}
                                        placeholder="Finalidade ou setor responsável..."
                                        value={costCenterForm.data.description}
                                        onChange={(e) => costCenterForm.setData('description', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                                <div className="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                                    <button type="button" onClick={() => setIsCostCenterModalOpen(false)} className="px-4 py-2 text-xs text-slate-400">Cancelar</button>
                                    <button type="submit" disabled={costCenterForm.processing} className="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg">Salvar Centro</button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </TenantDesktopLayout>
    );
}
