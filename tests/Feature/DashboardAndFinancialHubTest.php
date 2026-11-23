<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\CoreAdmin\Services\DefaultRolesSeederService;
use App\Modules\CoreAdmin\Services\DefaultStatusesSeederService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardAndFinancialHubTest extends TestCase
{
    private Tenant $tenant;
    private User $adminUser;
    private User $salesUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica & Brindes Premium Ltda',
            'subdomain' => 'premium-' . Str::random(6),
            'status' => 'active',
        ]);

        TenantContext::set($this->tenant);
        DefaultStatusesSeederService::seedForTenant($this->tenant->id);
        DefaultRolesSeederService::seedForTenant($this->tenant->id);

        $suffix = Str::random(6);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Diretor Premium',
            'email' => "diretor-{$suffix}@graficapremium.com",
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
            'is_super_admin' => false,
        ]);

        $this->salesUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Vendedor Premium',
            'email' => "vendedor-{$suffix}@graficapremium.com",
            'password' => Hash::make('password123'),
            'role' => 'sales',
            'is_active' => true,
            'is_super_admin' => false,
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_authenticated_tenant_user_can_access_dashboard_and_receive_kpis(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('tenant.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Dashboard/Index')
            ->has('tenant')
            ->has('kpis.financial')
            ->has('kpis.sales')
            ->has('kpis.production')
            ->has('kpis.inventory')
        );
    }

    public function test_root_url_redirects_authenticated_user_to_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/');

        $response->assertRedirect(route('tenant.dashboard'));
    }

    public function test_admin_can_access_unified_financial_hub_and_switch_tabs(): void
    {
        // 1. Aba Títulos
        $entriesResponse = $this->actingAs($this->adminUser)
            ->get(route('tenant.finance.index', ['tab' => 'entries']));

        $entriesResponse->assertOk();
        $entriesResponse->assertInertia(fn ($page) => $page
            ->component('Tenant/Finance/Hub')
            ->where('activeTab', 'entries')
            ->has('entries')
            ->has('bankAccounts')
            ->has('metrics')
        );

        // 2. Aba Relatórios & DRE
        $reportsResponse = $this->actingAs($this->adminUser)
            ->get(route('tenant.finance.index', ['tab' => 'reports']));

        $reportsResponse->assertOk();
        $reportsResponse->assertInertia(fn ($page) => $page
            ->component('Tenant/Finance/Hub')
            ->where('activeTab', 'reports')
            ->has('cashFlow')
            ->has('dre')
        );

        // 3. Aba Razão Kardex
        $kardexResponse = $this->actingAs($this->adminUser)
            ->get(route('tenant.finance.index', ['tab' => 'kardex']));

        $kardexResponse->assertOk();
        $kardexResponse->assertInertia(fn ($page) => $page
            ->component('Tenant/Finance/Hub')
            ->where('activeTab', 'kardex')
            ->has('movements')
            ->has('items')
            ->has('warehouses')
        );
    }

    public function test_user_without_finance_permission_cannot_access_financial_hub(): void
    {
        $response = $this->actingAs($this->salesUser)
            ->get(route('tenant.finance.index'));

        $response->assertStatus(403);
    }
}
