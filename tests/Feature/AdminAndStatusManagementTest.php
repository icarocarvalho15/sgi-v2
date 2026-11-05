<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Plan;
use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use App\Modules\CentralAdmin\Models\ImpersonationLog;
use App\Modules\CoreAdmin\Models\TenantStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAndStatusManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $tenantUser;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Alpha Express',
            'subdomain' => 'alpha-express',
            'status' => 'active',
        ]);

        $this->tenantUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Operador Alpha',
            'email' => 'operador@alpha.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'is_super_admin' => false,
        ]);

        $this->superAdmin = User::create([
            'name' => 'SaaS SuperAdmin',
            'email' => 'admin@landlord-saas.com',
            'password' => Hash::make('secretLandlord!'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_user_can_authenticate_and_logout(): void
    {
        $response = $this->post('/login', [
            'email' => 'operador@alpha.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/crm/partners');
        $this->assertAuthenticatedAs($this->tenantUser);

        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_non_superadmin_cannot_access_landlord_panel(): void
    {
        $this->actingAs($this->tenantUser);

        $response = $this->get('/admin/tenants');

        $response->assertStatus(403);
    }

    public function test_superadmin_can_access_landlord_panel_and_create_tenant(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get('/admin/tenants');
        $response->assertStatus(200);

        $storeResponse = $this->post('/admin/tenants', [
            'name' => 'Brindes Premium Brasil',
            'subdomain' => 'brindes-premium',
        ]);

        $storeResponse->assertRedirect('/admin/tenants');
        $this->assertDatabaseHas('tenants', [
            'name' => 'Brindes Premium Brasil',
            'subdomain' => 'brindes-premium',
        ]);
    }

    public function test_superadmin_can_impersonate_and_stop_impersonation_with_audit(): void
    {
        $this->actingAs($this->superAdmin);

        $impersonateResponse = $this->post("/admin/tenants/{$this->tenant->id}/impersonate", [
            'reason' => 'Verificação de suporte técnico #1092',
        ]);

        $impersonateResponse->assertRedirect('/crm/partners');

        $this->assertDatabaseHas('impersonation_logs', [
            'impersonator_id' => $this->superAdmin->id,
            'tenant_id' => $this->tenant->id,
            'reason' => 'Verificação de suporte técnico #1092',
        ]);

        $stopResponse = $this->post('/admin/impersonate/stop');
        $stopResponse->assertRedirect('/admin/tenants');
    }

    public function test_tenant_can_manage_custom_statuses(): void
    {
        $this->actingAs($this->tenantUser);
        session(['current_tenant_id' => $this->tenant->id]);

        $response = $this->get('/settings/statuses');
        $response->assertStatus(200);

        // Criar novo status
        $createResponse = $this->post('/settings/statuses', [
            'module' => 'production_orders',
            'name' => 'Aguardando Chapas CTP',
            'color' => '#f59e0b',
            'is_default' => false,
            'is_final' => false,
        ]);

        $createResponse->assertSessionHasNoErrors();
        $createResponse->assertRedirect('/settings/statuses');

        \App\Core\Tenancy\TenantContext::set($this->tenant);

        $this->assertDatabaseHas('tenant_statuses', [
            'tenant_id' => $this->tenant->id,
            'module' => 'production_orders',
            'name' => 'Aguardando Chapas CTP',
            'slug' => 'aguardando-chapas-ctp',
            'color' => '#f59e0b',
        ]);

        $status = TenantStatus::where('slug', 'aguardando-chapas-ctp')->firstOrFail();

        // Atualizar status
        $updateResponse = $this->put("/settings/statuses/{$status->id}", [
            'name' => 'Gravação CTP Concluída',
            'color' => '#10b981',
            'position' => 2,
            'is_default' => false,
            'is_final' => false,
        ]);

        $updateResponse->assertRedirect('/settings/statuses');

        \App\Core\Tenancy\TenantContext::set($this->tenant);

        $this->assertDatabaseHas('tenant_statuses', [
            'id' => $status->id,
            'name' => 'Gravação CTP Concluída',
            'slug' => 'gravacao-ctp-concluida',
            'color' => '#10b981',
        ]);

        // Deletar status
        $deleteResponse = $this->delete("/settings/statuses/{$status->id}");
        $deleteResponse->assertRedirect('/settings/statuses');

        \App\Core\Tenancy\TenantContext::set($this->tenant);

        $this->assertDatabaseMissing('tenant_statuses', [
            'id' => $status->id,
        ]);
    }
}
