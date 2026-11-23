import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import { 
    Building2, 
    Users, 
    ShieldCheck, 
    Palette, 
    Plus, 
    Edit2, 
    Trash2, 
    CheckCircle2, 
    XCircle, 
    Lock, 
    Mail, 
    Phone, 
    MapPin, 
    Save, 
    Key, 
    ShieldAlert, 
    Sliders,
    Layers,
    FileText,
    Kanban,
    CreditCard,
    Boxes,
    FileSpreadsheet,
    DollarSign,
    TrendingUp,
    CheckSquare,
    Square
} from 'lucide-react';

interface TenantData {
    id: string;
    name: string;
    trade_name?: string | null;
    document_number?: string | null;
    settings?: {
        state_registration?: string;
        municipal_registration?: string;
        phone?: string;
        email?: string;
        address?: {
            postal_code?: string;
            street?: string;
            number?: string;
            complement?: string;
            neighborhood?: string;
            city?: string;
            state?: string;
        };
    } | null;
}

interface TenantRole {
    id: string;
    name: string;
    slug: string;
    description?: string | null;
    color: string;
    permissions: string[];
    is_system: boolean;
}

interface Employee {
    id: string;
    name: string;
    email: string;
    role: string;
    role_id?: string | null;
    role_name: string;
    role_color: string;
    is_active: boolean;
    created_at: string;
}

interface FiscalSettings {
    cnpj: string;
    state_registration?: string;
    municipal_registration?: string;
    tax_regime: string;
    environment: string;
    default_ncm?: string;
    default_cfop?: string;
    certificate_expires_at?: string;
}

interface GatewayConfig {
    id: string;
    gateway_name: string;
    is_active: boolean;
    mode: string;
}

interface TenantStatus {
    id: string;
    tenant_id: string;
    module: string;
    name: string;
    slug: string;
    color: string;
    position: number;
    is_default: boolean;
    is_final: boolean;
}

interface Props {
    tenant: TenantData;
    employees: Employee[];
    roles: TenantRole[];
    availableModules: Record<string, { name: string; description: string }>;
    fiscalSettings: FiscalSettings | null;
    gateways: GatewayConfig[];
    hasCertificate: boolean;
    statusesByModule: {
        quotes: TenantStatus[];
        production_orders: TenantStatus[];
        crm_deals: TenantStatus[];
    };
    activeTab?: string;
}

export default function SettingsHub({ 
    tenant, 
    employees, 
    roles = [],
    availableModules = {},
    fiscalSettings, 
    gateways, 
    hasCertificate, 
    statusesByModule,
    activeTab: initialTab = 'company'
}: Props) {
    const [currentTab, setCurrentTab] = useState<'company' | 'employees' | 'fiscal' | 'statuses'>(
        (initialTab as any) || 'company'
    );

    // Modal de Cargos / Funções
    const [isRoleModalOpen, setIsRoleModalOpen] = useState(false);
    const [editingRole, setEditingRole] = useState<TenantRole | null>(null);

    // Modal de Funcionários
    const [isEmployeeModalOpen, setIsEmployeeModalOpen] = useState(false);
    const [editingEmployee, setEditingEmployee] = useState<Employee | null>(null);

    // Modal de Status
    const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
    const [editingStatus, setEditingStatus] = useState<TenantStatus | null>(null);
    const [statusModule, setStatusModule] = useState<'quotes' | 'production_orders' | 'crm_deals'>('quotes');

    // Estado para busca de CEP da empresa
    const [loadingCep, setLoadingCep] = useState(false);
    const [cepError, setCepError] = useState<string | null>(null);

    // 1. FORMULÁRIO DE DADOS DA EMPRESA
    const companyForm = useForm({
        name: tenant.name || '',
        trade_name: tenant.trade_name || '',
        document_number: tenant.document_number || '',
        state_registration: tenant.settings?.state_registration || fiscalSettings?.state_registration || '',
        municipal_registration: tenant.settings?.municipal_registration || fiscalSettings?.municipal_registration || '',
        phone: tenant.settings?.phone || '',
        email: tenant.settings?.email || '',
        postal_code: tenant.settings?.address?.postal_code || '',
        street: tenant.settings?.address?.street || '',
        number: tenant.settings?.address?.number || '',
        complement: tenant.settings?.address?.complement || '',
        neighborhood: tenant.settings?.address?.neighborhood || '',
        city: tenant.settings?.address?.city || '',
        state: tenant.settings?.address?.state || '',
    });

    // 2. FORMULÁRIO DE CARGO / FUNÇÃO
    const roleForm = useForm({
        name: '',
        description: '',
        color: '#6366F1',
        permissions: [] as string[],
    });

    // 3. FORMULÁRIO DE FUNCIONÁRIO (Criação/Edição)
    const employeeForm = useForm({
        name: '',
        email: '',
        role_id: roles[0]?.id || '',
        role: roles[0]?.slug || 'sales',
        password: '',
        is_active: true,
    });

    // 4. FORMULÁRIO FISCAL & GATEWAYS
    const fiscalForm = useForm({
        cnpj: fiscalSettings?.cnpj || tenant.document_number || '',
        state_registration: fiscalSettings?.state_registration || '',
        municipal_registration: fiscalSettings?.municipal_registration || '',
        tax_regime: fiscalSettings?.tax_regime || 'simples_nacional',
        environment: fiscalSettings?.environment || 'sandbox',
        default_ncm: fiscalSettings?.default_ncm || '49111090',
        default_cfop: fiscalSettings?.default_cfop || '5101',
        certificate_file: null as File | null,
        certificate_password: '',
        gateway_name: 'asaas',
        gateway_api_key: '',
        gateway_webhook_secret: '',
    });

    // 5. FORMULÁRIO DE STATUS
    const statusForm = useForm({
        module: statusModule,
        name: '',
        color: '#6366f1',
        position: 1,
        is_default: false,
        is_final: false,
    });

    // Sincronizar Aba na URL
    const handleTabChange = (tab: 'company' | 'employees' | 'fiscal' | 'statuses') => {
        setCurrentTab(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url.toString());
    };

    // Busca de CEP Automática para a Empresa (ViaCEP com fallback BrasilAPI)
    const handleCepLookup = async (cepRaw: string) => {
        const cleanCep = cepRaw.replace(/\D/g, '');
        companyForm.setData('postal_code', cleanCep);

        if (cleanCep.length !== 8) return;

        setLoadingCep(true);
        setCepError(null);

        try {
            const viaCepRes = await fetch(`https://viacep.com.br/ws/${cleanCep}/json/`);
            if (viaCepRes.ok) {
                const data = await viaCepRes.json();
                if (!data.erro) {
                    companyForm.setData((prev) => ({
                        ...prev,
                        street: data.logradouro || prev.street,
                        neighborhood: data.bairro || prev.neighborhood,
                        city: data.localidade || prev.city,
                        state: data.uf || prev.state,
                    }));
                    setLoadingCep(false);
                    return;
                }
            }

            const brasilApiRes = await fetch(`https://brasilapi.com.br/api/cep/v1/${cleanCep}`);
            if (brasilApiRes.ok) {
                const data = await brasilApiRes.json();
                companyForm.setData((prev) => ({
                    ...prev,
                    street: data.street || prev.street,
                    neighborhood: data.neighborhood || prev.neighborhood,
                    city: data.city || prev.city,
                    state: data.state || prev.state,
                }));
            } else {
                setCepError('CEP não localizado nas bases públicas.');
            }
        } catch (err) {
            setCepError('Falha ao consultar serviço de CEP.');
        } finally {
            setLoadingCep(false);
        }
    };

    // Submissão Dados da Empresa
    const handleCompanySubmit = (e: React.FormEvent) => {
        e.preventDefault();
        companyForm.put('/settings/company');
    };

    // =========================================================================
    // GESTÃO DE CARGOS / FUNÇÕES
    // =========================================================================
    const openCreateRoleModal = () => {
        setEditingRole(null);
        roleForm.reset({
            name: '',
            description: '',
            color: '#6366F1',
            permissions: ['quotes', 'crm'],
        });
        setIsRoleModalOpen(true);
    };

    const openEditRoleModal = (r: TenantRole) => {
        setEditingRole(r);
        roleForm.setData({
            name: r.name,
            description: r.description || '',
            color: r.color,
            permissions: r.permissions || [],
        });
        setIsRoleModalOpen(true);
    };

    const togglePermission = (modKey: string) => {
        const current = roleForm.data.permissions;
        if (current.includes(modKey)) {
            roleForm.setData('permissions', current.filter((k) => k !== modKey));
        } else {
            roleForm.setData('permissions', [...current, modKey]);
        }
    };

    const handleRoleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingRole) {
            roleForm.put(`/settings/roles/${editingRole.id}`, {
                onSuccess: () => setIsRoleModalOpen(false),
            });
        } else {
            roleForm.post('/settings/roles', {
                onSuccess: () => setIsRoleModalOpen(false),
            });
        }
    };

    const handleDeleteRole = (r: TenantRole) => {
        if (r.is_system || r.slug === 'admin') {
            alert('O cargo de Dono / Administrador é protegido pelo sistema e não pode ser excluído.');
            return;
        }

        if (confirm(`Tem certeza que deseja remover o cargo "${r.name}"?`)) {
            router.delete(`/settings/roles/${r.id}`);
        }
    };

    // =========================================================================
    // GESTÃO DE FUNCIONÁRIOS
    // =========================================================================
    const openCreateEmployeeModal = () => {
        setEditingEmployee(null);
        const defaultRole = roles.find((r) => r.slug === 'sales') || roles[0];
        employeeForm.reset({
            name: '',
            email: '',
            role_id: defaultRole?.id || '',
            role: defaultRole?.slug || 'sales',
            password: '',
            is_active: true,
        });
        setIsEmployeeModalOpen(true);
    };

    const openEditEmployeeModal = (emp: Employee) => {
        setEditingEmployee(emp);
        employeeForm.setData({
            name: emp.name,
            email: emp.email,
            role_id: emp.role_id || '',
            role: emp.role,
            password: '',
            is_active: emp.is_active,
        });
        setIsEmployeeModalOpen(true);
    };

    const handleEmployeeSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingEmployee) {
            employeeForm.put(`/settings/employees/${editingEmployee.id}`, {
                onSuccess: () => setIsEmployeeModalOpen(false),
            });
        } else {
            employeeForm.post('/settings/employees', {
                onSuccess: () => setIsEmployeeModalOpen(false),
            });
        }
    };

    const handleDeleteEmployee = (emp: Employee) => {
        if (confirm(`Tem certeza que deseja remover o funcionário ${emp.name}?`)) {
            router.delete(`/settings/employees/${emp.id}`);
        }
    };

    // Submissão Fiscal
    const handleFiscalSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fiscalForm.post('/settings/fiscal', {
            forceFormData: true,
        });
    };

    // Status Customizados
    const openCreateStatusModal = () => {
        setEditingStatus(null);
        statusForm.reset({
            module: statusModule,
            name: '',
            color: '#6366f1',
            position: (statusesByModule[statusModule]?.length || 0) + 1,
            is_default: false,
            is_final: false,
        });
        setIsStatusModalOpen(true);
    };

    const openEditStatusModal = (st: TenantStatus) => {
        setEditingStatus(st);
        statusForm.setData({
            module: st.module,
            name: st.name,
            color: st.color,
            position: st.position,
            is_default: st.is_default,
            is_final: st.is_final,
        });
        setIsStatusModalOpen(true);
    };

    const handleStatusSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingStatus) {
            statusForm.put(`/settings/statuses/${editingStatus.id}`, {
                onSuccess: () => setIsStatusModalOpen(false),
            });
        } else {
            statusForm.setData('module', statusModule);
            statusForm.post('/settings/statuses', {
                onSuccess: () => setIsStatusModalOpen(false),
            });
        }
    };

    const handleDeleteStatus = (st: TenantStatus) => {
        if (confirm(`Remover o status "${st.name}"?`)) {
            router.delete(`/settings/statuses/${st.id}`);
        }
    };

    return (
        <AppLayout title="Configurações da Empresa">
            <Head title="Configurações Gerais da Empresa - SGI v2.0" />

            <div className="space-y-6 max-w-6xl mx-auto">
                {/* Header Superior */}
                <div className="bg-slate-900/80 p-6 rounded-2xl border border-slate-800 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center gap-1.5">
                                <Building2 className="w-3.5 h-3.5" /> Administração da Empresa
                            </span>
                            <span className="text-xs text-slate-500">•</span>
                            <span className="text-xs text-slate-400 font-mono">{tenant.name}</span>
                        </div>
                        <h1 className="text-2xl font-bold text-white tracking-tight">
                            Configurações Gerais & Níveis de Acesso
                        </h1>
                        <p className="text-sm text-slate-400 mt-0.5">
                            Gerencie os dados cadastrais da gráfica, equipe de funcionários, cargos customizáveis com suas atribuições, parâmetros fiscais e etapas operacionais.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <ShieldCheck className="w-4 h-4" />
                            Isolamento RLS Ativo
                        </span>
                    </div>
                </div>

                {/* BARRA DE NAVEGAÇÃO DE ABAS */}
                <div className="flex gap-2 border-b border-slate-800 pb-2 overflow-x-auto">
                    <button
                        onClick={() => handleTabChange('company')}
                        className={`px-4 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2.5 transition-all shrink-0 ${
                            currentTab === 'company'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-semibold'
                                : 'bg-slate-900/60 text-slate-400 hover:text-slate-200 border border-slate-800/80'
                        }`}
                    >
                        <Building2 className="w-4 h-4" />
                        Dados da Empresa
                    </button>

                    <button
                        onClick={() => handleTabChange('employees')}
                        className={`px-4 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2.5 transition-all shrink-0 ${
                            currentTab === 'employees'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-semibold'
                                : 'bg-slate-900/60 text-slate-400 hover:text-slate-200 border border-slate-800/80'
                        }`}
                    >
                        <Users className="w-4 h-4" />
                        Funcionários & Permissões
                        <span className="px-2 py-0.5 rounded-full text-xs bg-slate-800 text-slate-300">
                            {employees.length}
                        </span>
                    </button>

                    <button
                        onClick={() => handleTabChange('fiscal')}
                        className={`px-4 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2.5 transition-all shrink-0 ${
                            currentTab === 'fiscal'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-semibold'
                                : 'bg-slate-900/60 text-slate-400 hover:text-slate-200 border border-slate-800/80'
                        }`}
                    >
                        <CreditCard className="w-4 h-4" />
                        Fiscal & Gateways
                    </button>

                    <button
                        onClick={() => handleTabChange('statuses')}
                        className={`px-4 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2.5 transition-all shrink-0 ${
                            currentTab === 'statuses'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-semibold'
                                : 'bg-slate-900/60 text-slate-400 hover:text-slate-200 border border-slate-800/80'
                        }`}
                    >
                        <Palette className="w-4 h-4" />
                        Status Customizados
                    </button>
                </div>

                {/* ========================================================================= */}
                {/* ABA 1: DADOS DA EMPRESA                                                  */}
                {/* ========================================================================= */}
                {currentTab === 'company' && (
                    <form onSubmit={handleCompanySubmit} className="space-y-6">
                        <div className="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                            <h2 className="text-base font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                                <Building2 className="w-5 h-5 text-indigo-400" />
                                Informações Cadastrais da Empresa
                            </h2>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Razão Social da Gráfica / Indústria *
                                    </label>
                                    <input
                                        type="text"
                                        value={companyForm.data.name}
                                        onChange={(e) => companyForm.setData('name', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                        required
                                    />
                                    {companyForm.errors.name && (
                                        <p className="text-xs text-rose-400 mt-1">{companyForm.errors.name}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Nome Fantasia
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Ex: Gráfica Express & Brindes"
                                        value={companyForm.data.trade_name}
                                        onChange={(e) => companyForm.setData('trade_name', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        CNPJ da Empresa
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="00.000.000/0001-00"
                                        value={companyForm.data.document_number}
                                        onChange={(e) => companyForm.setData('document_number', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Inscrição Estadual (IE)
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Ex: 111.222.333.444"
                                        value={companyForm.data.state_registration}
                                        onChange={(e) => companyForm.setData('state_registration', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Inscrição Municipal (IM)
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Ex: 98765432"
                                        value={companyForm.data.municipal_registration}
                                        onChange={(e) => companyForm.setData('municipal_registration', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Telefone Comercial / WhatsApp
                                    </label>
                                    <div className="relative">
                                        <Phone className="w-4 h-4 text-slate-500 absolute left-3.5 top-3" />
                                        <input
                                            type="text"
                                            placeholder="(11) 98765-4321"
                                            value={companyForm.data.phone}
                                            onChange={(e) => companyForm.setData('phone', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        E-mail Institucional
                                    </label>
                                    <div className="relative">
                                        <Mail className="w-4 h-4 text-slate-500 absolute left-3.5 top-3" />
                                        <input
                                            type="email"
                                            placeholder="contato@graficaexpress.com.br"
                                            value={companyForm.data.email}
                                            onChange={(e) => companyForm.setData('email', e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Endereço da Empresa com Busca de CEP */}
                        <div className="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                                <h2 className="text-base font-bold text-white flex items-center gap-2">
                                    <MapPin className="w-5 h-5 text-indigo-400" />
                                    Endereço da Sede / Parque Gráfico
                                </h2>
                                <span className="text-xs text-indigo-400">Preenchimento automático via CEP</span>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        CEP (Somente dígitos)
                                    </label>
                                    <div className="relative">
                                        <input
                                            type="text"
                                            maxLength={8}
                                            placeholder="Ex: 01310100"
                                            value={companyForm.data.postal_code}
                                            onChange={(e) => handleCepLookup(e.target.value)}
                                            className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 font-mono focus:outline-none focus:border-indigo-500"
                                        />
                                        {loadingCep && (
                                            <div className="absolute right-3 top-3 text-[11px] text-indigo-400 animate-pulse">
                                                Buscando...
                                            </div>
                                        )}
                                    </div>
                                    {cepError && <p className="text-xs text-rose-400 mt-1">{cepError}</p>}
                                </div>

                                <div className="sm:col-span-2">
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Logradouro (Rua, Avenida, Alameda)
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Av. Paulista"
                                        value={companyForm.data.street}
                                        onChange={(e) => companyForm.setData('street', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Número</label>
                                    <input
                                        type="text"
                                        placeholder="1000"
                                        value={companyForm.data.number}
                                        onChange={(e) => companyForm.setData('number', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Complemento / Galpão</label>
                                    <input
                                        type="text"
                                        placeholder="Galpão 3 / Sala 102"
                                        value={companyForm.data.complement}
                                        onChange={(e) => companyForm.setData('complement', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Bairro</label>
                                    <input
                                        type="text"
                                        placeholder="Bela Vista"
                                        value={companyForm.data.neighborhood}
                                        onChange={(e) => companyForm.setData('neighborhood', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Cidade / UF</label>
                                    <div className="flex gap-2">
                                        <input
                                            type="text"
                                            placeholder="São Paulo"
                                            value={companyForm.data.city}
                                            onChange={(e) => companyForm.setData('city', e.target.value)}
                                            className="w-2/3 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                        />
                                        <input
                                            type="text"
                                            maxLength={2}
                                            placeholder="SP"
                                            value={companyForm.data.state}
                                            onChange={(e) => companyForm.setData('state', e.target.value.toUpperCase())}
                                            className="w-1/3 bg-slate-950 border border-slate-800 rounded-xl px-2 py-2.5 text-sm text-slate-100 uppercase text-center focus:outline-none focus:border-indigo-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <button
                                type="submit"
                                disabled={companyForm.processing}
                                className="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-semibold transition-all shadow-lg shadow-indigo-600/20 disabled:opacity-50"
                            >
                                <Save className="w-4 h-4" />
                                {companyForm.processing ? 'Salvando...' : 'Salvar Informações da Empresa'}
                            </button>
                        </div>
                    </form>
                )}

                {/* ========================================================================= */}
                {/* ABA 2: FUNCIONÁRIOS & PERMISSÕES (COM CARGOS DINÂMICOS)                   */}
                {/* ========================================================================= */}
                {currentTab === 'employees' && (
                    <div className="space-y-8">
                        {/* SEÇÃO 1: CARGOS & FUNÇÕES CUSTOMIZADAS DA EMPRESA */}
                        <div className="space-y-4">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h3 className="text-base font-bold text-white flex items-center gap-2">
                                            <Sliders className="w-4 h-4 text-indigo-400" />
                                            Cargos, Funções & Atribuições da Empresa
                                        </h3>
                                        <span className="text-xs px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                            {roles.length} cadastrados
                                        </span>
                                    </div>
                                    <p className="text-xs text-slate-400 mt-0.5">
                                        Personalize a nomenclatura dos cargos da sua gráfica e defina exatamente quais módulos cada função tem permissão de operar.
                                    </p>
                                </div>

                                <button
                                    onClick={openCreateRoleModal}
                                    className="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-indigo-300 hover:text-white rounded-xl text-xs font-semibold transition-all border border-slate-700/80 shadow"
                                >
                                    <Plus className="w-4 h-4 text-indigo-400" />
                                    Nova Função / Cargo
                                </button>
                            </div>

                            {/* Grid de Cargos */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                {roles.map((r) => {
                                    const isRootAdmin = r.slug === 'admin' || r.is_system;

                                    return (
                                        <div
                                            key={r.id}
                                            className="p-4 bg-slate-900/60 border border-slate-800 rounded-2xl flex flex-col justify-between space-y-3 hover:border-slate-700/80 transition-all shadow-sm"
                                        >
                                            <div className="space-y-2">
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="flex items-center gap-2 min-w-0">
                                                        <span
                                                            className="w-3 h-3 rounded-full shrink-0"
                                                            style={{ backgroundColor: r.color }}
                                                        />
                                                        <h4 className="font-semibold text-xs text-white truncate">
                                                            {r.name}
                                                        </h4>
                                                    </div>

                                                    <div className="flex items-center gap-1 shrink-0">
                                                        {isRootAdmin ? (
                                                            <span className="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-indigo-400 border border-indigo-500/20">
                                                                Protegido
                                                            </span>
                                                        ) : (
                                                            <>
                                                                <button
                                                                    onClick={() => openEditRoleModal(r)}
                                                                    className="p-1 text-slate-400 hover:text-white hover:bg-slate-800 rounded transition-colors"
                                                                    title="Editar cargo e permissões"
                                                                >
                                                                    <Edit2 className="w-3.5 h-3.5" />
                                                                </button>
                                                                <button
                                                                    onClick={() => handleDeleteRole(r)}
                                                                    className="p-1 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded transition-colors"
                                                                    title="Remover cargo"
                                                                >
                                                                    <Trash2 className="w-3.5 h-3.5" />
                                                                </button>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>

                                                <p className="text-[11px] text-slate-400 line-clamp-2 leading-relaxed min-h-[2rem]">
                                                    {r.description || 'Sem descrição cadastrada.'}
                                                </p>
                                            </div>

                                            {/* Pílulas de Módulos Autorizados */}
                                            <div className="pt-2 border-t border-slate-800/80">
                                                <span className="text-[10px] text-slate-500 font-medium block mb-1">
                                                    Módulos Autorizados:
                                                </span>
                                                <div className="flex flex-wrap gap-1">
                                                    {r.slug === 'admin' ? (
                                                        <span className="text-[9px] px-1.5 py-0.5 rounded bg-indigo-500/15 text-indigo-300 border border-indigo-500/20 font-medium">
                                                            Acesso Total a Todos os Módulos
                                                        </span>
                                                    ) : (
                                                        (r.permissions || []).map((modKey) => (
                                                            <span
                                                                key={modKey}
                                                                className="text-[9px] px-1.5 py-0.5 rounded bg-slate-800/90 text-slate-300 border border-slate-700/50"
                                                            >
                                                                {availableModules[modKey]?.name || modKey}
                                                            </span>
                                                        ))
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* SEÇÃO 2: EQUIPE DE FUNCIONÁRIOS */}
                        <div className="space-y-4 pt-4 border-t border-slate-800/80">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-base font-bold text-white flex items-center gap-2">
                                        <Users className="w-4 h-4 text-indigo-400" />
                                        Equipe de Funcionários da Gráfica
                                    </h3>
                                    <p className="text-xs text-slate-400 mt-0.5">
                                        Cadastre e gerencie os operadores, vendedores e gerentes com credenciais exclusivas e isolamento de módulos.
                                    </p>
                                </div>

                                <button
                                    onClick={openCreateEmployeeModal}
                                    className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition-all shadow-md shadow-indigo-600/20"
                                >
                                    <Plus className="w-4 h-4" />
                                    Cadastrar Novo Funcionário
                                </button>
                            </div>

                            {/* Tabela de Funcionários */}
                            <div className="bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-sm text-slate-300">
                                        <thead className="bg-slate-800/40 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                                            <tr>
                                                <th className="px-6 py-4">Nome do Funcionário</th>
                                                <th className="px-6 py-4">E-mail de Acesso</th>
                                                <th className="px-6 py-4">Perfil / Função</th>
                                                <th className="px-6 py-4">Status</th>
                                                <th className="px-6 py-4 text-right">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-800/60">
                                            {employees.length === 0 ? (
                                                <tr>
                                                    <td colSpan={5} className="px-6 py-12 text-center text-slate-500">
                                                        Nenhum funcionário cadastrado.
                                                    </td>
                                                </tr>
                                            ) : (
                                                employees.map((emp) => (
                                                    <tr key={emp.id} className="hover:bg-slate-800/30 transition-colors">
                                                        <td className="px-6 py-4">
                                                            <div className="flex items-center gap-3">
                                                                <div className="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center font-bold text-xs text-indigo-400 border border-slate-700">
                                                                    {emp.name.substring(0, 2).toUpperCase()}
                                                                </div>
                                                                <span className="font-medium text-slate-200">
                                                                    {emp.name}
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 font-mono text-xs text-slate-400">
                                                            {emp.email}
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <span
                                                                className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border"
                                                                style={{
                                                                    color: emp.role_color,
                                                                    borderColor: `${emp.role_color}40`,
                                                                    backgroundColor: `${emp.role_color}15`,
                                                                }}
                                                            >
                                                                <span
                                                                    className="w-1.5 h-1.5 rounded-full"
                                                                    style={{ backgroundColor: emp.role_color }}
                                                                />
                                                                {emp.role_name}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            {emp.is_active ? (
                                                                <span className="inline-flex items-center gap-1 text-xs text-emerald-400">
                                                                    <CheckCircle2 className="w-3.5 h-3.5" /> Ativo
                                                                </span>
                                                            ) : (
                                                                <span className="inline-flex items-center gap-1 text-xs text-rose-400">
                                                                    <XCircle className="w-3.5 h-3.5" /> Inativo
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="px-6 py-4 text-right">
                                                            <div className="flex items-center justify-end gap-2">
                                                                <button
                                                                    onClick={() => openEditEmployeeModal(emp)}
                                                                    className="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors"
                                                                    title="Editar dados ou permissões"
                                                                >
                                                                    <Edit2 className="w-3.5 h-3.5" />
                                                                </button>
                                                                <button
                                                                    onClick={() => handleDeleteEmployee(emp)}
                                                                    className="p-1.5 rounded-lg bg-slate-800 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 transition-colors"
                                                                    title="Remover funcionário"
                                                                >
                                                                    <Trash2 className="w-3.5 h-3.5" />
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* ========================================================================= */}
                {/* ABA 3: FISCAL & GATEWAYS DE PAGAMENTO                                    */}
                {/* ========================================================================= */}
                {currentTab === 'fiscal' && (
                    <form onSubmit={handleFiscalSubmit} className="space-y-6">
                        {/* Dados Fiscais */}
                        <div className="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                            <h2 className="text-base font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                                <ShieldCheck className="w-5 h-5 text-indigo-400" />
                                Parâmetros Tributários & Emissão Fiscal (NFe/NFSe)
                            </h2>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        CNPJ da Gráfica / Indústria *
                                    </label>
                                    <input
                                        type="text"
                                        value={fiscalForm.data.cnpj}
                                        onChange={(e) => fiscalForm.setData('cnpj', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                        required
                                    />
                                    {fiscalForm.errors.cnpj && (
                                        <p className="text-xs text-rose-400 mt-1">{fiscalForm.errors.cnpj}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Inscrição Estadual (IE)
                                    </label>
                                    <input
                                        type="text"
                                        value={fiscalForm.data.state_registration}
                                        onChange={(e) => fiscalForm.setData('state_registration', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Inscrição Municipal (IM)
                                    </label>
                                    <input
                                        type="text"
                                        value={fiscalForm.data.municipal_registration}
                                        onChange={(e) => fiscalForm.setData('municipal_registration', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Regime Tributário *
                                    </label>
                                    <select
                                        value={fiscalForm.data.tax_regime}
                                        onChange={(e) => fiscalForm.setData('tax_regime', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="simples_nacional">Simples Nacional</option>
                                        <option value="lucro_presumido">Lucro Presumido</option>
                                        <option value="lucro_real">Lucro Real</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Ambiente de Emissão *
                                    </label>
                                    <select
                                        value={fiscalForm.data.environment}
                                        onChange={(e) => fiscalForm.setData('environment', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="sandbox">Homologação / Testes</option>
                                        <option value="production">Produção Oficial (SEFAZ)</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        NCM Padrão (Impressos)
                                    </label>
                                    <input
                                        type="text"
                                        value={fiscalForm.data.default_ncm}
                                        onChange={(e) => fiscalForm.setData('default_ncm', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        CFOP Padrão (Saída)
                                    </label>
                                    <input
                                        type="text"
                                        value={fiscalForm.data.default_cfop}
                                        onChange={(e) => fiscalForm.setData('default_cfop', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Certificado Digital A1 */}
                        <div className="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                                <h2 className="text-base font-bold text-white flex items-center gap-2">
                                    <Key className="w-5 h-5 text-amber-400" />
                                    Certificado Digital A1 (.pfx / .p12)
                                </h2>
                                {hasCertificate ? (
                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <CheckCircle2 className="w-3.5 h-3.5" />
                                        Certificado Instalado no Cofre
                                    </span>
                                ) : (
                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        <ShieldAlert className="w-3.5 h-3.5" />
                                        Certificado Pendente
                                    </span>
                                )}
                            </div>

                            <div className="p-4 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-slate-400 flex items-start gap-3">
                                <Lock className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                                <span>
                                    O arquivo binário <code>.pfx</code> e a senha são criptografados através do cofre <strong>VaultEncryption (AES-256-GCM)</strong> com chave derivada exclusivamente para sua empresa via HKDF.
                                </span>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Arquivo do Certificado (.pfx / .p12)
                                    </label>
                                    <input
                                        type="file"
                                        accept=".pfx,.p12"
                                        onChange={(e) => fiscalForm.setData('certificate_file', e.target.files?.[0] || null)}
                                        className="w-full text-xs text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Senha do Certificado Digital
                                    </label>
                                    <input
                                        type="password"
                                        placeholder="••••••••••••"
                                        value={fiscalForm.data.certificate_password}
                                        onChange={(e) => fiscalForm.setData('certificate_password', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Gateways de Pagamento */}
                        <div className="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                            <h2 className="text-base font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                                <CreditCard className="w-5 h-5 text-indigo-400" />
                                Gateways de Pagamento (Asaas, Mercado Pago, Stripe)
                            </h2>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Provedor</label>
                                    <select
                                        value={fiscalForm.data.gateway_name}
                                        onChange={(e) => fiscalForm.setData('gateway_name', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500"
                                    >
                                        <option value="asaas">Asaas (Boleto & Pix Gráfico)</option>
                                        <option value="mercadopago">Mercado Pago</option>
                                        <option value="stripe">Stripe</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Chave de API / Access Token</label>
                                    <input
                                        type="password"
                                        placeholder="$aact_YTU5YTE0M2M6..."
                                        value={fiscalForm.data.gateway_api_key}
                                        onChange={(e) => fiscalForm.setData('gateway_api_key', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono text-xs"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">Webhook Secret / HMAC</label>
                                    <input
                                        type="password"
                                        placeholder="whsec_..."
                                        value={fiscalForm.data.gateway_webhook_secret}
                                        onChange={(e) => fiscalForm.setData('gateway_webhook_secret', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500 font-mono text-xs"
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <button
                                type="submit"
                                disabled={fiscalForm.processing}
                                className="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-semibold transition-all shadow-lg shadow-indigo-600/20 disabled:opacity-50"
                            >
                                <Save className="w-4 h-4" />
                                {fiscalForm.processing ? 'Salvando...' : 'Salvar Parâmetros Fiscais & Gateways'}
                            </button>
                        </div>
                    </form>
                )}

                {/* ========================================================================= */}
                {/* ABA 4: STATUS CUSTOMIZADOS                                               */}
                {/* ========================================================================= */}
                {currentTab === 'statuses' && (
                    <div className="space-y-6">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-5 rounded-2xl border border-slate-800">
                            <div>
                                <h3 className="text-base font-bold text-white">Etapas dos Módulos Operacionais</h3>
                                <p className="text-xs text-slate-400 mt-0.5">
                                    Configure as colunas do funil comercial e as fases de produção da sua gráfica sem travas hardcoded.
                                </p>
                            </div>

                            <button
                                onClick={openCreateStatusModal}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition-all shadow-md shadow-indigo-600/20"
                            >
                                <Plus className="w-4 h-4" />
                                Adicionar Status na Etapa
                            </button>
                        </div>

                        {/* Seletor do Módulo de Status */}
                        <div className="flex gap-2">
                            <button
                                onClick={() => setStatusModule('quotes')}
                                className={`px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all ${
                                    statusModule === 'quotes'
                                        ? 'bg-indigo-600 text-white shadow'
                                        : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
                                }`}
                            >
                                <FileText className="w-3.5 h-3.5" />
                                Orçamentos & Funil de Vendas ({statusesByModule.quotes?.length || 0})
                            </button>
                            <button
                                onClick={() => setStatusModule('production_orders')}
                                className={`px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all ${
                                    statusModule === 'production_orders'
                                        ? 'bg-indigo-600 text-white shadow'
                                        : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
                                }`}
                            >
                                <Layers className="w-3.5 h-3.5" />
                                Chão de Fábrica & OPs ({statusesByModule.production_orders?.length || 0})
                            </button>
                            <button
                                onClick={() => setStatusModule('crm_deals')}
                                className={`px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all ${
                                    statusModule === 'crm_deals'
                                        ? 'bg-indigo-600 text-white shadow'
                                        : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
                                }`}
                            >
                                <Kanban className="w-3.5 h-3.5" />
                                Oportunidades CRM ({statusesByModule.crm_deals?.length || 0})
                            </button>
                        </div>

                        {/* Grid de Status */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            {(statusesByModule[statusModule] || []).length === 0 ? (
                                <div className="col-span-full bg-slate-900/40 border border-slate-800/80 rounded-2xl p-12 text-center text-slate-500">
                                    Nenhum status cadastrado para este módulo.
                                </div>
                            ) : (
                                (statusesByModule[statusModule] || []).map((st) => (
                                    <div
                                        key={st.id}
                                        className="p-5 bg-slate-900/60 border border-slate-800 rounded-2xl flex flex-col justify-between space-y-4 hover:border-slate-700/80 transition-all"
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex items-center gap-2.5">
                                                <span
                                                    className="w-3 h-3 rounded-full"
                                                    style={{ backgroundColor: st.color }}
                                                />
                                                <div>
                                                    <h4 className="font-semibold text-sm text-slate-200">
                                                        {st.name}
                                                    </h4>
                                                    <span className="text-[11px] font-mono text-slate-500 block">
                                                        slug: {st.slug}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-1.5">
                                                <button
                                                    onClick={() => openEditStatusModal(st)}
                                                    className="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors"
                                                >
                                                    <Edit2 className="w-3.5 h-3.5" />
                                                </button>
                                                <button
                                                    onClick={() => handleDeleteStatus(st)}
                                                    className="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors"
                                                >
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                        </div>

                                        <div className="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-800/80">
                                            <span>Posição: {st.position}</span>
                                            {st.is_default && (
                                                <span className="text-[10px] text-sky-400 font-medium">Inicial / Padrão</span>
                                            )}
                                            {st.is_final && (
                                                <span className="text-[10px] text-emerald-400 font-medium">Conclusivo</span>
                                            )}
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                )}
            </div>

            {/* ========================================================================= */}
            {/* MODAL: CADASTRO / EDIÇÃO DE CARGO & ATRIBUIÇÕES                           */}
            {/* ========================================================================= */}
            {isRoleModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl p-6 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                <Sliders className="w-5 h-5 text-indigo-400" />
                                {editingRole ? `Editar Cargo: ${editingRole.name}` : 'Novo Cargo / Função'}
                            </h3>
                            <button
                                onClick={() => setIsRoleModalOpen(false)}
                                className="text-slate-400 hover:text-white"
                            >
                                <XCircle className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleRoleSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Título / Nome do Cargo *
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ex: Orçamentista Técnico Gráfico, Impressor Offset, etc."
                                    value={roleForm.data.name}
                                    onChange={(e) => roleForm.setData('name', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    required
                                    disabled={editingRole?.is_system && editingRole?.slug === 'admin'}
                                />
                                {roleForm.errors.name && (
                                    <p className="text-xs text-rose-400 mt-1">{roleForm.errors.name}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Cor Identificadora do Cargo
                                    </label>
                                    <div className="flex items-center gap-2 bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5">
                                        <input
                                            type="color"
                                            value={roleForm.data.color}
                                            onChange={(e) => roleForm.setData('color', e.target.value)}
                                            className="w-7 h-7 rounded-lg bg-transparent cursor-pointer border-0"
                                        />
                                        <span className="font-mono text-xs text-slate-300">
                                            {roleForm.data.color}
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Descrição Breve das Responsabilidades
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Ex: Atendimento a agências e cálculo de pré-impressão"
                                        value={roleForm.data.description}
                                        onChange={(e) => roleForm.setData('description', e.target.value)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            {/* MÓDULOS & PERMISSÕES */}
                            <div className="pt-2">
                                <label className="block text-xs font-medium text-slate-300 mb-2">
                                    Atribuições & Módulos Autorizados para este Cargo:
                                </label>

                                {editingRole?.slug === 'admin' ? (
                                    <div className="p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-xs text-indigo-300">
                                        Este é o cargo master de Dono / Administrador. Ele possui acesso total a todos os módulos do ERP por definição de segurança.
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        {Object.entries(availableModules).map(([modKey, modMeta]) => {
                                            const isChecked = roleForm.data.permissions.includes(modKey);

                                            return (
                                                <div
                                                    key={modKey}
                                                    onClick={() => togglePermission(modKey)}
                                                    className={`p-3 rounded-xl border cursor-pointer transition-all flex items-start gap-2.5 ${
                                                        isChecked
                                                            ? 'bg-indigo-600/15 border-indigo-500/60 text-white'
                                                            : 'bg-slate-950/80 border-slate-800/80 text-slate-400 hover:border-slate-700'
                                                    }`}
                                                >
                                                    <div className="pt-0.5">
                                                        {isChecked ? (
                                                            <CheckSquare className="w-4 h-4 text-indigo-400" />
                                                        ) : (
                                                            <Square className="w-4 h-4 text-slate-600" />
                                                        )}
                                                    </div>
                                                    <div>
                                                        <h5 className="font-semibold text-xs leading-snug">
                                                            {modMeta.name}
                                                        </h5>
                                                        <p className="text-[10px] text-slate-500 leading-tight mt-0.5">
                                                            {modMeta.description}
                                                        </p>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                                {roleForm.errors.permissions && (
                                    <p className="text-xs text-rose-400 mt-1">{roleForm.errors.permissions}</p>
                                )}
                            </div>

                            <div className="flex justify-end gap-3 pt-3 border-t border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setIsRoleModalOpen(false)}
                                    className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={roleForm.processing}
                                    className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-600/20 disabled:opacity-50"
                                >
                                    {roleForm.processing ? 'Salvando...' : 'Salvar Cargo'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* ========================================================================= */}
            {/* MODAL: CADASTRO / EDIÇÃO DE FUNCIONÁRIO                                  */}
            {/* ========================================================================= */}
            {isEmployeeModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-5">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-lg font-bold text-white flex items-center gap-2">
                                <Users className="w-5 h-5 text-indigo-400" />
                                {editingEmployee ? 'Editar Funcionário' : 'Novo Funcionário'}
                            </h3>
                            <button
                                onClick={() => setIsEmployeeModalOpen(false)}
                                className="text-slate-400 hover:text-white"
                            >
                                <XCircle className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleEmployeeSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Nome Completo *
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ex: Carlos Silva"
                                    value={employeeForm.data.name}
                                    onChange={(e) => employeeForm.setData('name', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    required
                                />
                                {employeeForm.errors.name && (
                                    <p className="text-xs text-rose-400 mt-1">{employeeForm.errors.name}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    E-mail de Login *
                                </label>
                                <input
                                    type="email"
                                    placeholder="carlos@empresa.com"
                                    value={employeeForm.data.email}
                                    onChange={(e) => employeeForm.setData('email', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    required
                                />
                                {employeeForm.errors.email && (
                                    <p className="text-xs text-rose-400 mt-1">{employeeForm.errors.email}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Cargo / Atribuição *
                                </label>
                                <select
                                    value={employeeForm.data.role_id || employeeForm.data.role}
                                    onChange={(e) => {
                                        const selectedRole = roles.find((r) => r.id === e.target.value || r.slug === e.target.value);
                                        if (selectedRole) {
                                            employeeForm.setData((prev) => ({
                                                ...prev,
                                                role_id: selectedRole.id,
                                                role: selectedRole.slug,
                                            }));
                                        }
                                    }}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                >
                                    {roles.map((r) => (
                                        <option key={r.id} value={r.id}>
                                            {r.name}
                                        </option>
                                    ))}
                                </select>
                                {roles.find((r) => r.id === employeeForm.data.role_id || r.slug === employeeForm.data.role)?.description && (
                                    <p className="text-[11px] text-slate-500 mt-1">
                                        {roles.find((r) => r.id === employeeForm.data.role_id || r.slug === employeeForm.data.role)?.description}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    {editingEmployee ? 'Nova Senha (deixe em branco para manter a atual)' : 'Senha Inicial *'}
                                </label>
                                <input
                                    type="password"
                                    placeholder="••••••••"
                                    value={employeeForm.data.password}
                                    onChange={(e) => employeeForm.setData('password', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    required={!editingEmployee}
                                />
                                {employeeForm.errors.password && (
                                    <p className="text-xs text-rose-400 mt-1">{employeeForm.errors.password}</p>
                                )}
                            </div>

                            {editingEmployee && (
                                <div className="flex items-center gap-2 pt-2">
                                    <input
                                        type="checkbox"
                                        id="is_active_emp"
                                        checked={employeeForm.data.is_active}
                                        onChange={(e) => employeeForm.setData('is_active', e.target.checked)}
                                        className="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-0"
                                    />
                                    <label htmlFor="is_active_emp" className="text-xs text-slate-300">
                                        Funcionário ativo e autorizado a acessar o sistema
                                    </label>
                                </div>
                            )}

                            <div className="flex justify-end gap-3 pt-3 border-t border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setIsEmployeeModalOpen(false)}
                                    className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={employeeForm.processing}
                                    className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-600/20 disabled:opacity-50"
                                >
                                    {employeeForm.processing ? 'Salvando...' : 'Salvar Funcionário'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* ========================================================================= */}
            {/* MODAL: CADASTRO / EDIÇÃO DE STATUS                                       */}
            {/* ========================================================================= */}
            {isStatusModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
                        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 className="text-base font-bold text-white flex items-center gap-2">
                                <Palette className="w-5 h-5 text-indigo-400" />
                                {editingStatus ? 'Editar Status' : 'Novo Status'}
                            </h3>
                            <button
                                onClick={() => setIsStatusModalOpen(false)}
                                className="text-slate-400 hover:text-white"
                            >
                                <XCircle className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleStatusSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Nome da Etapa *
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ex: Em Gravação CTP"
                                    value={statusForm.data.name}
                                    onChange={(e) => statusForm.setData('name', e.target.value)}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    required
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Cor Identificadora
                                    </label>
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="color"
                                            value={statusForm.data.color}
                                            onChange={(e) => statusForm.setData('color', e.target.value)}
                                            className="w-9 h-9 rounded-lg bg-transparent cursor-pointer border-0"
                                        />
                                        <span className="font-mono text-xs text-slate-300">
                                            {statusForm.data.color}
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-400 mb-1">
                                        Ordem / Posição
                                    </label>
                                    <input
                                        type="number"
                                        value={statusForm.data.position}
                                        onChange={(e) => statusForm.setData('position', parseInt(e.target.value) || 1)}
                                        className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2 pt-2">
                                <div className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="st_default"
                                        checked={statusForm.data.is_default}
                                        onChange={(e) => statusForm.setData('is_default', e.target.checked)}
                                        className="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-0"
                                    />
                                    <label htmlFor="st_default" className="text-xs text-slate-300">
                                        Status inicial padrão deste módulo
                                    </label>
                                </div>

                                <div className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="st_final"
                                        checked={statusForm.data.is_final}
                                        onChange={(e) => statusForm.setData('is_final', e.target.checked)}
                                        className="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-0"
                                    />
                                    <label htmlFor="st_final" className="text-xs text-slate-300">
                                        Status conclusivo / final (encerrado)
                                    </label>
                                </div>
                            </div>

                            <div className="flex justify-end gap-3 pt-3 border-t border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setIsStatusModalOpen(false)}
                                    className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={statusForm.processing}
                                    className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-600/20 disabled:opacity-50"
                                >
                                    {statusForm.processing ? 'Salvando...' : 'Salvar Status'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
