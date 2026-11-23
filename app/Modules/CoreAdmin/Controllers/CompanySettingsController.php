<?php

declare(strict_types=1);

namespace App\Modules\CoreAdmin\Controllers;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\Integrations\Models\PaymentGatewayConfig;
use App\Modules\Integrations\Models\TenantFiscalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingsController extends Controller
{
    /**
     * Hub Central de Configurações da Empresa (Abas: Empresa, Funcionários & Permissões, Fiscal/Gateways, Status).
     */
    public function index(Request $request): Response
    {
        $tenant = TenantContext::get();
        if (! $tenant) {
            abort(404, 'Empresa não identificada no contexto atual.');
        }

        $activeTab = $request->query('tab', 'company');

        // 1. Dados da Empresa e Funcionários
        $employees = User::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at']);

        // 2. Configurações Fiscais & Meios de Pagamento
        $fiscalSettings = TenantFiscalSetting::where('tenant_id', $tenant->id)->first();
        $gateways = PaymentGatewayConfig::where('tenant_id', $tenant->id)->get();

        // 3. Status Customizáveis
        $statuses = TenantStatus::where('tenant_id', $tenant->id)
            ->orderBy('module')
            ->orderBy('position')
            ->get();

        $statusesByModule = [
            'quotes' => $statuses->where('module', 'quotes')->values(),
            'production_orders' => $statuses->where('module', 'production_orders')->values(),
            'crm_deals' => $statuses->where('module', 'crm_deals')->values(),
        ];

        $rolesDefinition = [
            'admin' => [
                'name' => 'Dono / Administrador Geral',
                'description' => 'Acesso total irrestrito a todos os módulos, financeiro confidencial, DRE, PCP e configurações.',
                'color' => '#6366F1',
                'modules' => ['Clientes', 'Orçamentos', 'Engenharia (BOM)', 'Chão de Fábrica (OPs)', 'Estoque & Kardex', 'Financeiro & DRE', 'Configurações'],
            ],
            'sales' => [
                'name' => 'Vendedor / Comercial',
                'description' => 'Focado na captação de clientes, elaboração de orçamentos técnicos gráficos e acompanhamento do funil de vendas.',
                'color' => '#0EA5E9',
                'modules' => ['Clientes & Fornecedores', 'Orçamentos Técnicos & Funil', 'Consulta de Estoque'],
            ],
            'production' => [
                'name' => 'Chão de Fábrica / PCP',
                'description' => 'Acesso à engenharia gráfica, fichas técnicas (BOM), execução de ordens de produção e controle de almoxarifado/insumos.',
                'color' => '#F59E0B',
                'modules' => ['Fichas Técnicas (BOM)', 'Chão de Fábrica (OPs)', 'Apontamento de Horas', 'Estoque & Kardex'],
            ],
            'finance' => [
                'name' => 'Financeiro / Fiscal',
                'description' => 'Gestão de contas a pagar/receber, conciliação, fluxo de caixa, DRE gerencial e parâmetros fiscais/NFe.',
                'color' => '#10B981',
                'modules' => ['Clientes & Fornecedores', 'Financeiro (Títulos)', 'Fluxo & DRE', 'Configurações Fiscais'],
            ],
        ];

        return Inertia::render('Tenant/Settings/Index', [
            'tenant' => $tenant,
            'employees' => $employees,
            'fiscalSettings' => $fiscalSettings,
            'gateways' => $gateways,
            'hasCertificate' => ! empty($fiscalSettings?->certificate_a1_encrypted),
            'statusesByModule' => $statusesByModule,
            'activeTab' => $activeTab,
            'rolesDefinition' => $rolesDefinition,
        ]);
    }

    /**
     * Atualização dos Dados Cadastrais e Endereço da Empresa.
     */
    public function updateCompany(Request $request): RedirectResponse
    {
        $tenant = TenantContext::get();
        if (! $tenant) {
            abort(404, 'Empresa não identificada.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:20'],
            'state_registration' => ['nullable', 'string', 'max:30'],
            'municipal_registration' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:30'],
            'complement' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:2'],
        ]);

        $currentSettings = $tenant->settings ?? [];
        $newSettings = array_merge($currentSettings, [
            'state_registration' => $validated['state_registration'] ?? null,
            'municipal_registration' => $validated['municipal_registration'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => [
                'postal_code' => $validated['postal_code'] ?? null,
                'street' => $validated['street'] ?? null,
                'number' => $validated['number'] ?? null,
                'complement' => $validated['complement'] ?? null,
                'neighborhood' => $validated['neighborhood'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
            ],
        ]);

        $tenant->update([
            'name' => $validated['name'],
            'trade_name' => $validated['trade_name'] ?? null,
            'document_number' => $validated['document_number'] ?? null,
            'settings' => $newSettings,
        ]);

        // Sincroniza dados com o registro fiscal se existir
        if (! empty($validated['document_number'])) {
            $fiscal = TenantFiscalSetting::where('tenant_id', $tenant->id)->first();
            if ($fiscal) {
                $fiscal->update([
                    'cnpj' => $validated['document_number'],
                    'state_registration' => $validated['state_registration'] ?? $fiscal->state_registration,
                    'municipal_registration' => $validated['municipal_registration'] ?? $fiscal->municipal_registration,
                ]);
            }
        }

        return redirect()->route('tenant.settings.index', ['tab' => 'company'])
            ->with('success', 'Dados da empresa atualizados com sucesso!');
    }

    /**
     * Cadastro de Novo Funcionário / Usuário da Empresa.
     */
    public function storeEmployee(Request $request): RedirectResponse
    {
        $tenant = TenantContext::get();
        if (! $tenant) {
            abort(404, 'Empresa não identificada.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:admin,sales,production,finance'],
        ]);

        $employee = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
            'is_super_admin' => false,
        ]);

        // Vincula também à pivot tenant_user
        $tenant->members()->syncWithoutDetaching([
            $employee->id => [
                'id' => (string) Str::uuid(),
                'role' => $validated['role'],
                'is_active' => true,
            ],
        ]);

        return redirect()->route('tenant.settings.index', ['tab' => 'employees'])
            ->with('success', "Funcionário {$employee->name} cadastrado com sucesso com perfil de {$employee->role_name}!");
    }

    /**
     * Atualização de Funcionário (Nome, Perfil, Status, Senha opcional).
     */
    public function updateEmployee(Request $request, User $user): RedirectResponse
    {
        $tenant = TenantContext::get();
        if (! $tenant || $user->tenant_id !== $tenant->id) {
            abort(403, 'Você não tem permissão para gerenciar este usuário.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'role' => ['required', 'string', 'in:admin,sales,production,finance'],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $validated['is_active'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Atualiza pivot tenant_user
        $tenant->members()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
            'is_active' => $validated['is_active'],
        ]);

        return redirect()->route('tenant.settings.index', ['tab' => 'employees'])
            ->with('success', "Funcionário {$user->name} atualizado com sucesso!");
    }

    /**
     * Remoção de Funcionário da Empresa.
     */
    public function destroyEmployee(Request $request, User $user): RedirectResponse
    {
        $tenant = TenantContext::get();
        if (! $tenant || $user->tenant_id !== $tenant->id) {
            abort(403, 'Você não tem permissão para remover este usuário.');
        }

        if ($request->user()?->id === $user->id) {
            return back()->with('error', 'Você não pode excluir o seu próprio usuário logado.');
        }

        $name = $user->name;
        $tenant->members()->detach($user->id);
        $user->delete();

        return redirect()->route('tenant.settings.index', ['tab' => 'employees'])
            ->with('success', "Funcionário {$name} removido com sucesso!");
    }
}
