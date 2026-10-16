<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\ImpersonationLog;
use App\Core\Tenancy\Models\Plan;
use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Database\Seeders\CoreLandlordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CentralAdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $tenantUser;

    private Tenant $tenantAlfa;

    private Plan $starterPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CoreLandlordSeeder::class);

        /** @var User $superAdmin */
        $superAdmin = User::query()->where('email', 'admin@sgi.com.br')->firstOrFail();
        $this->superAdmin = $superAdmin;

        /** @var User $tenantUser */
        $tenantUser = User::query()->where('email', 'alfa.admin@metalurgicaalfa.com.br')->firstOrFail();
        $this->tenantUser = $tenantUser;

        /** @var Tenant $tenantAlfa */
        $tenantAlfa = Tenant::query()->where('subdomain', 'alfa')->firstOrFail();
        $this->tenantAlfa = $tenantAlfa;

        /** @var Plan $starterPlan */
        $starterPlan = Plan::query()->where('slug', 'starter')->firstOrFail();
        $this->starterPlan = $starterPlan;
    }

    /**
     * Valida que usuários não autenticados são redirecionados para a tela de login ao tentar acessar /admin.
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    /**
     * Valida que usuário comum de um Tenant recebe HTTP 403 Forbidden ao tentar invadir o CentralAdmin (Landlord).
     */
    public function test_regular_tenant_user_receives_403_forbidden_on_central_admin_routes(): void
    {
        $this->actingAs($this->tenantUser);

        // 1. Dashboard Landlord
        $response = $this->get('/admin');
        $response->assertStatus(403);

        // 2. Lista de Tenants
        $responseTenants = $this->get('/admin/tenants');
        $responseTenants->assertStatus(403);

        // 3. Gestão de Planos
        $responsePlans = $this->get('/admin/plans');
        $responsePlans->assertStatus(403);

        // 4. Tentativa de iniciar Impersonation
        $responseImpersonate = $this->post("/admin/impersonate/{$this->tenantAlfa->id}", [
            'reason' => 'Tentativa de escalonamento indevida',
        ]);
        $responseImpersonate->assertStatus(403);
    }

    /**
     * Valida que o Super Admin acessa normalmente o CentralAdmin.
     */
    public function test_super_admin_can_access_central_admin(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get('/admin');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('CentralAdmin/Dashboard'));
    }

    /**
     * Valida o provisionamento atômico de uma nova empresa pelo Super Admin.
     */
    public function test_super_admin_can_provision_new_tenant_atomically(): void
    {
        $this->actingAs($this->superAdmin);

        $payload = [
            'name' => 'Fábrica Gama Mecânica Ltda',
            'trade_name' => 'Gama Mecânica',
            'document_number' => '55.444.333/0001-22',
            'subdomain' => 'gama',
            'plan_id' => $this->starterPlan->id,
            'owner_name' => 'Roberto Diretor',
            'owner_email' => 'roberto@gamamecanica.com.br',
            'owner_password' => 'segredo123',
        ];

        $response = $this->post('/admin/tenants', $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Valida criação no banco
        $this->assertDatabaseHas('tenants', [
            'subdomain' => 'gama',
            'name' => 'Fábrica Gama Mecânica Ltda',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'roberto@gamamecanica.com.br',
            'is_super_admin' => false,
        ]);

        $newTenant = Tenant::query()->where('subdomain', 'gama')->firstOrFail();
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $newTenant->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
        ]);
    }

    /**
     * Valida que o fluxo de Impersonation exige motivo obrigatório e grava auditoria compulsória.
     */
    public function test_impersonation_requires_reason_and_logs_audit_trail(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Tentativa sem motivo (deve falhar na validação)
        $responseFail = $this->post("/admin/impersonate/{$this->tenantAlfa->id}", [
            'reason' => '',
        ]);
        $responseFail->assertSessionHasErrors('reason');

        // 2. Com motivo válido
        $responseSuccess = $this->post("/admin/impersonate/{$this->tenantAlfa->id}", [
            'reason' => 'Verificação de erro reportado no chamado #9872',
        ]);

        $responseSuccess->assertRedirect(route('tenant.dashboard'));
        $this->assertEquals($this->tenantAlfa->id, session('active_tenant_id'));
        $this->assertTrue(session('is_impersonating'));

        // Valida que o log imutável de auditoria foi gravado
        $this->assertDatabaseHas('impersonation_logs', [
            'impersonator_id' => $this->superAdmin->id,
            'tenant_id' => $this->tenantAlfa->id,
            'reason' => 'Verificação de erro reportado no chamado #9872',
        ]);
    }

    /**
     * Valida que encerrar a sessão de Impersonation preenche ended_at e limpa a sessão.
     */
    public function test_stopping_impersonation_closes_log_and_cleans_session(): void
    {
        $this->actingAs($this->superAdmin);

        // Inicia
        $this->post("/admin/impersonate/{$this->tenantAlfa->id}", [
            'reason' => 'Auditoria técnica de infraestrutura',
        ]);

        /** @var ImpersonationLog $log */
        $log = ImpersonationLog::query()->where('tenant_id', $this->tenantAlfa->id)->latest()->firstOrFail();
        $this->assertNull($log->ended_at);

        // Encerra
        $responseLeave = $this->post('/admin/impersonate/leave');
        $responseLeave->assertRedirect(route('admin.dashboard'));

        $this->assertNull(session('active_tenant_id'));
        $this->assertNull(session('is_impersonating'));

        $log->refresh();
        $this->assertNotNull($log->ended_at);
    }

    /**
     * Valida o fluxo de seleção e troca de empresa ativa.
     */
    public function test_tenant_selection_flow(): void
    {
        $this->actingAs($this->tenantUser);

        // Seleciona o tenant Alfa
        $response = $this->post("/select-tenant/{$this->tenantAlfa->id}");
        $response->assertRedirect(route('tenant.dashboard'));
        $this->assertEquals($this->tenantAlfa->id, session('active_tenant_id'));
    }
}
