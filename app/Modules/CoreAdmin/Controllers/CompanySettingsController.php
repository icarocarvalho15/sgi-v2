<?php

declare(strict_types=1);

namespace App\Modules\CoreAdmin\Controllers;

use App\Core\Security\Rules\TenantExists;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\CoreAdmin\Models\TenantRole;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\CoreAdmin\Services\DefaultRolesSeederService;
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

        // Garante que o tenant tenha os cargos padrão se ainda não foram criados
        if (! TenantRole::where('tenant_id', $tenant->id)->exists()) {
            DefaultRolesSeederService::seedForTenant($tenant->id);
        }

        // 1. Cargos & Funções da Empresa
        $roles = TenantRole::where('tenant_id', $tenant->id)
            ->ordered()
            ->get(['id', 'name', 'slug', 'description', 'color', 'permissions', 'is_system']);

        $availableModules = DefaultRolesSeederService::getAvailableModules();

        // 2. Dados da Empresa e Funcionários
        $employees = User::where('tenant_id', $tenant->id)
            ->with('tenantRole')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'role_id', 'is_active', 'created_at']);

        // 3. Configurações Fiscais & Meios de Pagamento
        $fiscalSettings = TenantFiscalSetting::where('tenant_id', $tenant->id)->first();
        $gateways = PaymentGatewayConfig::where('tenant_id', $tenant->id)->get();

        // 4. Status Customizáveis
        $statuses = TenantStatus::where('tenant_id', $tenant->id)
            ->orderBy('module')
            ->orderBy('position')
            ->get();

        $statusesByModule = [
            'quotes' => $statuses->where('module', 'quotes')->values(),
            'production_orders' => $statuses->where('module', 'production_orders')->values(),
            'crm_deals' => $statuses->where('module', 'crm_deals')->values(),
        ];

        return Inertia::render('Tenant/Settings/Index', [
            'tenant' => $tenant,
            'employees' => $employees,
            'roles' => $roles,
            'availableModules' => $availableModules,
            'fiscalSettings' => $fiscalSettings,
            'gateways' => $gateways,
            'hasCertificate' => ! empty($fiscalSettings?->certificate_a1_encrypted),
            'statusesByModule' => $statusesByModule,
            'activeTab' => $activeTab,
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
     * Criação de Novo Cargo / Função Customizada.
     */
    public function storeRole(Request $request): RedirectResponse
    {
        $tenant = TenantContext::get();
        if (! $tenant) {
            abort(404, 'Empresa não identificada.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
        ]);

        $role = TenantRole::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'],
            'permissions' => $validated['permissions'],
            'is_system' => false,
        ]);

        return redirect()->route('tenant.settings.index', ['tab' => 'employees'])
            ->with('success', "Cargo \"{$role->name}\" criado com sucesso!");
    }

    /**
     * Atualização de Cargo / Função Customizada.
     */
    public function updateRole(Request $request, TenantRole $role): RedirectResponse
    {
        $tenant = TenantContext::get();
        if (! $tenant || $role->tenant_id !== $tenant->id) {
            abort(403, 'Você não tem permissão para editar este cargo.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'],
        ];

        // Se for o cargo do sistema Dono / Administrador, mantém permissão total
        if (! $role->is_system) {
            $updateData['permissions'] = $validated['permissions'];
            $updateData['slug'] = Str::slug($validated['name']);
        }

        $role->update($updateData);

        return redirect()->route('tenant.settings.index', ['tab' => 'employees'])
            ->with('success', "Cargo \"{$role->name}\" atualizado com sucesso!");
    }

    /**
     * Remoção de Cargo / Função Customizada.
     */
    public function destroyRole(Request $request, TenantRole $role): RedirectResponse
    {
        $tenant = TenantContext::get();
        if (! $tenant || $role->tenant_id !== $tenant->id) {
            abort(403, 'Você não tem permissão para remover este cargo.');
        }

        if ($role->is_system || $role->slug === 'admin') {
            return back()->with('error', 'O cargo de Dono / Administrador é protegido pelo sistema e não pode ser excluído.');
        }

        // Verifica se há funcionários com este cargo
        $hasUsers = User::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($role) {
                $q->where('role_id', $role->id)
                  ->orWhere('role', $role->slug);
            })
            ->exists();

        if ($hasUsers) {
            return back()->with('error', "Não é possível excluir o cargo \"{$role->name}\" pois existem funcionários com esta função. Reatribua-os antes de excluir.");
        }

        $name = $role->name;
        $role->delete();

        return redirect()->route('tenant.settings.index', ['tab' => 'employees'])
            ->with('success', "Cargo \"{$name}\" removido com sucesso!");
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
            'role' => ['nullable', 'string', 'max:100'],
            'role_id' => ['nullable', 'uuid', new TenantExists('tenant_roles', 'id')],
        ]);

        $roleId = $validated['role_id'] ?? null;
        $roleSlug = $validated['role'] ?? 'sales';

        if (! empty($roleId)) {
            $roleObj = TenantRole::where('tenant_id', $tenant->id)->find($roleId);
            if ($roleObj) {
                $roleSlug = $roleObj->slug;
            }
        } elseif (! empty($roleSlug)) {
            $roleObj = TenantRole::where('tenant_id', $tenant->id)->where('slug', $roleSlug)->first();
            if ($roleObj) {
                $roleId = $roleObj->id;
            }
        }

        $employee = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $roleSlug,
            'role_id' => $roleId,
            'is_active' => true,
            'is_super_admin' => false,
        ]);

        // Vincula também à pivot tenant_user
        $tenant->members()->syncWithoutDetaching([
            $employee->id => [
                'id' => (string) Str::uuid(),
                'role' => $roleSlug,
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
            'role' => ['nullable', 'string', 'max:100'],
            'role_id' => ['nullable', 'uuid', new TenantExists('tenant_roles', 'id')],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $roleId = $validated['role_id'] ?? $user->role_id;
        $roleSlug = $validated['role'] ?? $user->role;

        if (! empty($validated['role_id'])) {
            $roleObj = TenantRole::where('tenant_id', $tenant->id)->find($validated['role_id']);
            if ($roleObj) {
                $roleSlug = $roleObj->slug;
                $roleId = $roleObj->id;
            }
        } elseif (! empty($validated['role'])) {
            $roleObj = TenantRole::where('tenant_id', $tenant->id)->where('slug', $validated['role'])->first();
            if ($roleObj) {
                $roleId = $roleObj->id;
                $roleSlug = $roleObj->slug;
            }
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $roleSlug,
            'role_id' => $roleId,
            'is_active' => $validated['is_active'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Atualiza pivot tenant_user
        $tenant->members()->updateExistingPivot($user->id, [
            'role' => $roleSlug,
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
