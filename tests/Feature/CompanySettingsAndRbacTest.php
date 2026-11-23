<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\CoreAdmin\Models\TenantRole;
use App\Modules\CoreAdmin\Services\DefaultStatusesSeederService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanySettingsAndRbacTest extends TestCase
{
    private Tenant $tenant;
    private User $adminUser;
    private User $salesUser;
    private User $productionUser;
    private User $financeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Express & Embalagens Ltda',
            'subdomain' => 'express-' . Str::random(6),
            'status' => 'active',
        ]);

        TenantContext::set($this->tenant);
        DefaultStatusesSeederService::seedForTenant($this->tenant->id);
        \App\Modules\CoreAdmin\Services\DefaultRolesSeederService::seedForTenant($this->tenant->id);

        $suffix = Str::random(6);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Dono Administrador',
            'email' => "admin-{$suffix}@graficaexpress.com",
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
            'is_super_admin' => false,
        ]);

        $this->salesUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Vendedor Comercial',
            'email' => "vendas-{$suffix}@graficaexpress.com",
            'password' => Hash::make('password123'),
            'role' => 'sales',
            'is_active' => true,
            'is_super_admin' => false,
        ]);

        $this->productionUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Operador de CTP e Offset',
            'email' => "producao-{$suffix}@graficaexpress.com",
            'password' => Hash::make('password123'),
            'role' => 'production',
            'is_active' => true,
            'is_super_admin' => false,
        ]);

        $this->financeUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Analista Financeiro',
            'email' => "financeiro-{$suffix}@graficaexpress.com",
            'password' => Hash::make('password123'),
            'role' => 'finance',
            'is_active' => true,
            'is_super_admin' => false,
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_admin_can_access_company_settings_hub(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('tenant.settings.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Settings/Index')
            ->has('tenant')
            ->has('employees', 4)
            ->has('statusesByModule')
            ->has('roles')
            ->has('availableModules')
        );
    }

    public function test_admin_can_update_company_profile_and_address(): void
    {
        $payload = [
            'name' => 'Gráfica Express & Embalagens Personalizadas S/A',
            'trade_name' => 'Express Embalagens',
            'document_number' => '12.345.678/0001-99',
            'state_registration' => '111.222.333.444',
            'municipal_registration' => '98765432',
            'phone' => '(11) 98765-4321',
            'email' => 'contato@expressembalagens.com.br',
            'postal_code' => '01310100',
            'street' => 'Avenida Paulista',
            'number' => '1578',
            'complement' => 'Andar 10',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('tenant.settings.company.update'), $payload);

        $response->assertRedirect(route('tenant.settings.index', ['tab' => 'company']));

        TenantContext::set($this->tenant);
        $this->tenant->refresh();
        $this->assertEquals('Gráfica Express & Embalagens Personalizadas S/A', $this->tenant->name);
        $this->assertEquals('Express Embalagens', $this->tenant->trade_name);
        $this->assertEquals('12.345.678/0001-99', $this->tenant->document_number);
        $this->assertEquals('01310100', $this->tenant->settings['address']['postal_code']);
        $this->assertEquals('Avenida Paulista', $this->tenant->settings['address']['street']);
    }

    public function test_admin_can_create_update_and_delete_employees(): void
    {
        $newEmail = 'operador.' . Str::random(6) . '@graficaexpress.com';

        // 1. Create Employee
        $storeResponse = $this->actingAs($this->adminUser)
            ->post(route('tenant.settings.employees.store'), [
                'name' => 'Novo Operador de Impressão',
                'email' => $newEmail,
                'password' => 'secret1234',
                'role' => 'production',
            ]);

        $storeResponse->assertRedirect(route('tenant.settings.index', ['tab' => 'employees']));

        $newEmployee = User::where('email', $newEmail)->first();
        $this->assertNotNull($newEmployee);
        $this->assertEquals($this->tenant->id, $newEmployee->tenant_id);
        $this->assertEquals('production', $newEmployee->role);
        $this->assertTrue($newEmployee->is_active);

        // 2. Update Employee
        $updateResponse = $this->actingAs($this->adminUser)
            ->put(route('tenant.settings.employees.update', $newEmployee), [
                'name' => 'Operador Offset Sênior',
                'email' => $newEmail,
                'role' => 'production',
                'is_active' => true,
            ]);

        $updateResponse->assertRedirect(route('tenant.settings.index', ['tab' => 'employees']));
        $newEmployee->refresh();
        $this->assertEquals('Operador Offset Sênior', $newEmployee->name);

        // 3. Delete Employee
        $deleteResponse = $this->actingAs($this->adminUser)
            ->delete(route('tenant.settings.employees.destroy', $newEmployee));

        $deleteResponse->assertRedirect(route('tenant.settings.index', ['tab' => 'employees']));
        $this->assertSoftDeleted('users', ['id' => $newEmployee->id]);
    }

    public function test_non_admin_cannot_access_company_settings(): void
    {
        $salesResponse = $this->actingAs($this->salesUser)
            ->get(route('tenant.settings.index'));
        $salesResponse->assertStatus(403);

        $prodResponse = $this->actingAs($this->productionUser)
            ->get(route('tenant.settings.index'));
        $prodResponse->assertStatus(403);
    }

    public function test_sales_user_can_access_quotes_but_not_finance(): void
    {
        // Sales tem acesso a orçamentos
        $quotesResponse = $this->actingAs($this->salesUser)
            ->get(route('quotes.index'));
        $quotesResponse->assertOk();

        // Sales NÃO tem acesso a financeiro confidencial
        $financeResponse = $this->actingAs($this->salesUser)
            ->get(route('tenant.finance.entries.index'));
        $financeResponse->assertStatus(403);
    }

    public function test_production_user_can_access_orders_but_not_quotes_or_finance(): void
    {
        // Produção tem acesso a Ordens de Produção
        $ordersResponse = $this->actingAs($this->productionUser)
            ->get(route('production.orders.index'));
        $ordersResponse->assertOk();

        // Produção NÃO tem acesso a Orçamentos
        $quotesResponse = $this->actingAs($this->productionUser)
            ->get(route('quotes.index'));
        $quotesResponse->assertStatus(403);

        // Produção NÃO tem acesso a Financeiro
        $financeResponse = $this->actingAs($this->productionUser)
            ->get(route('tenant.finance.entries.index'));
        $financeResponse->assertStatus(403);
    }

    public function test_inactive_user_is_blocked(): void
    {
        $this->salesUser->update(['is_active' => false]);

        $response = $this->actingAs($this->salesUser)
            ->get(route('quotes.index'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
    }

    public function test_admin_can_create_update_and_delete_custom_roles(): void
    {
        // 1. Create custom role
        $createResponse = $this->actingAs($this->adminUser)
            ->post(route('tenant.settings.roles.store'), [
                'name' => 'Orçamentista Gráfico',
                'description' => 'Responsável por orçamentos de offset e brindes',
                'color' => '#10B981',
                'permissions' => ['crm', 'quotes'],
            ]);

        $createResponse->assertRedirect(route('tenant.settings.index', ['tab' => 'employees']));

        TenantContext::set($this->tenant);
        $role = TenantRole::where('tenant_id', $this->tenant->id)
            ->where('slug', 'orcamentista-grafico')
            ->first();

        $this->assertNotNull($role);
        $this->assertEquals('Orçamentista Gráfico', $role->name);
        $this->assertEquals(['crm', 'quotes'], $role->permissions);
        $this->assertFalse($role->is_system);

        // 2. Update custom role
        $updateResponse = $this->actingAs($this->adminUser)
            ->put(route('tenant.settings.roles.update', $role), [
                'name' => 'Orçamentista Gráfico Sênior',
                'description' => 'Responsável sênior por orçamentos e fichas técnicas',
                'color' => '#059669',
                'permissions' => ['crm', 'quotes', 'boms'],
            ]);

        $updateResponse->assertRedirect(route('tenant.settings.index', ['tab' => 'employees']));

        TenantContext::set($this->tenant);
        $role->refresh();
        $this->assertEquals('Orçamentista Gráfico Sênior', $role->name);
        $this->assertEquals(['crm', 'quotes', 'boms'], $role->permissions);

        // 3. Delete custom role (no employees assigned)
        $deleteResponse = $this->actingAs($this->adminUser)
            ->delete(route('tenant.settings.roles.destroy', $role));

        $deleteResponse->assertRedirect(route('tenant.settings.index', ['tab' => 'employees']));

        TenantContext::set($this->tenant);
        $this->assertSoftDeleted('tenant_roles', ['id' => $role->id]);
    }

    public function test_admin_cannot_delete_root_admin_role(): void
    {
        TenantContext::set($this->tenant);
        $adminRole = TenantRole::where('tenant_id', $this->tenant->id)
            ->where('slug', 'admin')
            ->first();

        $this->assertNotNull($adminRole);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('tenant.settings.roles.destroy', $adminRole));

        $response->assertSessionHas('error');

        TenantContext::set($this->tenant);
        $this->assertDatabaseHas('tenant_roles', [
            'id' => $adminRole->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_cannot_delete_role_assigned_to_active_employees(): void
    {
        TenantContext::set($this->tenant);
        // Cria cargo
        $role = TenantRole::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Designer CTP',
            'slug' => 'designer-ctp',
            'color' => '#8B5CF6',
            'permissions' => ['boms'],
            'is_system' => false,
        ]);

        // Associa usuário ao cargo
        $designerUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Designer Teste',
            'email' => 'designer.' . Str::random(6) . '@graficaexpress.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'role' => $role->slug,
            'is_active' => true,
        ]);

        // Tenta excluir cargo
        $response = $this->actingAs($this->adminUser)
            ->delete(route('tenant.settings.roles.destroy', $role));

        $response->assertSessionHas('error');

        TenantContext::set($this->tenant);
        $this->assertDatabaseHas('tenant_roles', [
            'id' => $role->id,
            'deleted_at' => null,
        ]);
    }

    public function test_custom_role_permissions_accurately_control_module_access(): void
    {
        // Cria cargo com permissão apenas em Fichas Técnicas (BOM)
        $role = TenantRole::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Engenheiro de Produto',
            'slug' => 'engenheiro-produto',
            'color' => '#EC4899',
            'permissions' => ['boms'],
            'is_system' => false,
        ]);

        $engineerUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Engenheiro Gráfico',
            'email' => 'engenheiro.' . Str::random(6) . '@graficaexpress.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'role' => $role->slug,
            'is_active' => true,
        ]);

        // Pode acessar BOM
        $bomResponse = $this->actingAs($engineerUser)
            ->get(route('production.boms.index'));
        $bomResponse->assertOk();

        // Bloqueado em Orçamentos (403)
        $quotesResponse = $this->actingAs($engineerUser)
            ->get(route('quotes.index'));
        $quotesResponse->assertStatus(403);

        // Bloqueado em Financeiro (403)
        $financeResponse = $this->actingAs($engineerUser)
            ->get(route('tenant.finance.entries.index'));
        $financeResponse->assertStatus(403);
    }
}
