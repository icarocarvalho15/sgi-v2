<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Core\Security\Rules\TenantExists;
use App\Core\Tenancy\Database\PostgresRlsHelper;
use App\Core\Tenancy\Exceptions\CrossTenantTamperingException;
use App\Core\Tenancy\Exceptions\TenantContextMissingException;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Modules\CoreAdmin\Models\TenantStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    private Tenant $tenantA;
    private Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        TenantContext::clear();

        // Create two isolated Tenants (e.g. Gráfica Alpha and Brindes Beta)
        $this->tenantA = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Alpha Express',
            'subdomain' => 'alpha-' . Str::random(6),
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Indústria de Brindes Beta',
            'subdomain' => 'beta-' . Str::random(6),
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_postgresql_rls_natively_enforces_isolation_on_raw_sql_queries(): void
    {
        // 1. Insert records for both tenants using raw DB bypass
        $statusAId = (string) Str::uuid();
        $statusBId = (string) Str::uuid();

        // Temporarily set session to tenant A to insert Tenant A record
        PostgresRlsHelper::setSessionTenant($this->tenantA->id);
        DB::table('tenant_statuses')->insert([
            'id' => $statusAId,
            'tenant_id' => $this->tenantA->id,
            'module' => 'production_orders',
            'name' => 'Na Guilhotina Alpha',
            'slug' => 'na-guilhotina-alpha',
            'color' => '#3B82F6',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Switch postgres session to tenant B to insert Tenant B record
        PostgresRlsHelper::setSessionTenant($this->tenantB->id);
        DB::table('tenant_statuses')->insert([
            'id' => $statusBId,
            'tenant_id' => $this->tenantB->id,
            'module' => 'production_orders',
            'name' => 'Gravação Laser Brindes Beta',
            'slug' => 'gravacao-laser-beta',
            'color' => '#10B981',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Prova Real: Set PostgreSQL session to Tenant A and execute raw SQL WITHOUT Eloquent
        PostgresRlsHelper::setSessionTenant($this->tenantA->id);
        $rawResultsA = DB::table('tenant_statuses')->get();

        $this->assertTrue($rawResultsA->contains('id', $statusAId));
        $this->assertFalse($rawResultsA->contains('id', $statusBId), 'PostgreSQL RLS failed: Tenant B record leaked to Tenant A raw SQL query!');

        // Switch session to Tenant B and execute raw SQL
        PostgresRlsHelper::setSessionTenant($this->tenantB->id);
        $rawResultsB = DB::table('tenant_statuses')->get();

        $this->assertTrue($rawResultsB->contains('id', $statusBId));
        $this->assertFalse($rawResultsB->contains('id', $statusAId), 'PostgreSQL RLS failed: Tenant A record leaked to Tenant B raw SQL query!');

        // Fail-Closed check: Empty session setting returns 0 records
        PostgresRlsHelper::resetSessionTenant();
        $emptyResults = DB::table('tenant_statuses')->get();
        $this->assertCount(0, $emptyResults, 'PostgreSQL RLS Fail-Closed failed: Raw query returned records with empty session!');
    }

    public function test_eloquent_tenant_scope_fail_closed_when_context_is_empty(): void
    {
        // Populate records via TenantContext
        TenantContext::set($this->tenantA);
        TenantStatus::create([
            'module' => 'quotes',
            'name' => 'Rascunho CTP',
            'color' => '#64748B',
        ]);

        // Clear context: Eloquent query must return 0 records (Fail-Closed)
        TenantContext::clear();
        $this->assertCount(0, TenantStatus::all());
    }

    public function test_it_automatically_injects_tenant_id_on_model_creation(): void
    {
        TenantContext::set($this->tenantA);

        $status = TenantStatus::create([
            'module' => 'quotes',
            'name' => 'Aguardando Aprovação',
            'color' => '#F59E0B',
        ]);

        $this->assertEquals($this->tenantA->id, $status->tenant_id);
    }

    public function test_creating_tenant_model_without_context_throws_exception(): void
    {
        TenantContext::clear();

        $this->expectException(TenantContextMissingException::class);

        TenantStatus::create([
            'module' => 'quotes',
            'name' => 'Status Invalido Sem Contexto',
            'color' => '#EF4444',
        ]);
    }

    public function test_cross_tenant_spoofing_during_creation_is_strictly_blocked(): void
    {
        TenantContext::set($this->tenantA);

        $this->expectException(CrossTenantTamperingException::class);

        // Attempting to inject tenant B id while authenticated in tenant A context
        $status = new TenantStatus();
        $status->tenant_id = $this->tenantB->id;
        $status->module = 'quotes';
        $status->name = 'Malicious Status Injection';
        $status->color = '#EF4444';
        $status->save();
    }

    public function test_cross_tenant_tampering_on_update_is_strictly_blocked(): void
    {
        TenantContext::set($this->tenantA);

        $status = TenantStatus::create([
            'module' => 'quotes',
            'name' => 'Status Original',
            'color' => '#3B82F6',
        ]);

        $this->expectException(CrossTenantTamperingException::class);

        // Attempting to transfer record ownership to Tenant B
        $status->tenant_id = $this->tenantB->id;
        $status->save();
    }

    public function test_mass_assignment_cannot_alter_tenant_id(): void
    {
        TenantContext::set($this->tenantA);

        $status = TenantStatus::create([
            'module' => 'quotes',
            'name' => 'Status Protegido',
            'color' => '#10B981',
        ]);

        // Attempting mass-assignment modification of tenant_id
        $status->fill([
            'name' => 'Nome Atualizado',
            'tenant_id' => $this->tenantB->id,
        ]);
        $status->save();

        $status->refresh();
        $this->assertEquals($this->tenantA->id, $status->tenant_id);
        $this->assertEquals('Nome Atualizado', $status->name);
    }

    public function test_tenant_exists_validation_rule_prevents_cross_tenant_foreign_key_injection(): void
    {
        // Create a status in Tenant B
        TenantContext::set($this->tenantB);
        $statusB = TenantStatus::create([
            'module' => 'production_orders',
            'name' => 'Status Privado da Empresa B',
            'color' => '#6366F1',
        ]);

        // Now authenticate as Tenant A
        TenantContext::set($this->tenantA);

        // Create a status in Tenant A
        $statusA = TenantStatus::create([
            'module' => 'production_orders',
            'name' => 'Status Publico da Empresa A',
            'color' => '#14B8A6',
        ]);

        // 1. Tenant A trying to reference status from Tenant B (Ataque de FK Cross-Tenant)
        $validatorInvalid = Validator::make(
            ['status_id' => $statusB->id],
            ['status_id' => ['required', 'uuid', new TenantExists('tenant_statuses', 'id')]]
        );
        $this->assertTrue($validatorInvalid->fails(), 'TenantExists failed to block cross-tenant foreign key reference!');

        // 2. Tenant A referencing its own status
        $validatorValid = Validator::make(
            ['status_id' => $statusA->id],
            ['status_id' => ['required', 'uuid', new TenantExists('tenant_statuses', 'id')]]
        );
        $this->assertTrue($validatorValid->passes());
    }
}
