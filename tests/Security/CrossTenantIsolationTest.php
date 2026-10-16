<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Core\Security\VaultEncryption;
use App\Core\Tenancy\Database\PostgresRlsHelper;
use App\Core\Tenancy\Exceptions\CrossTenantTamperingException;
use App\Core\Tenancy\Exceptions\TenantContextMissingException;
use App\Core\Tenancy\Models\Plan;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Models\TenantModel;
use App\Core\Tenancy\Rules\TenantExists;
use App\Core\Tenancy\TenantContext;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

// Model concreta para testar as garantias do TenantModel
class DummyProject extends TenantModel
{
    protected $table = 'dummy_projects';

    protected $fillable = [
        'name',
    ];
}

final class CrossTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private TenantContext $context;

    private VaultEncryption $vault;

    private Tenant $tenantA;

    private Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = app(TenantContext::class);
        $this->vault = app(VaultEncryption::class);

        // Cria a tabela de teste de domínio
        if (! Schema::hasTable('dummy_projects')) {
            Schema::create('dummy_projects', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->string('name');
                $table->timestamps();

                $table->unique(['tenant_id', 'id']);
            });
        }

        // Cria plano e dois tenants isolados
        $plan = Plan::create([
            'name' => 'Plano Industrial Pro',
            'slug' => 'pro-industrial',
            'price' => 499.00,
            'max_users' => 10,
            'features' => ['nfe_enabled' => true],
        ]);

        $this->tenantA = Tenant::create([
            'name' => 'Metalúrgica Alpha Ltda',
            'subdomain' => 'alpha-ind',
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Usinagem Beta S/A',
            'subdomain' => 'beta-ind',
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $this->context->clear();
    }

    protected function tearDown(): void
    {
        $this->context->clear();
        parent::tearDown();
    }

    /**
     * Valida que o VaultEncryption impede descriptografia cruzada entre Tenants (Zero-Trust Key Derivation).
     */
    public function test_vault_encryption_prevents_cross_tenant_decryption(): void
    {
        $sensitivePayload = 'CERTIFICADO_A1_SUPER_SECRETO_EMPRESA_A';

        // Tenant A cifra seu segredo
        $ciphertext = $this->vault->encrypt($sensitivePayload, $this->tenantA->id, 'fiscal_a1');

        // Tenant A descriptografa com sucesso
        $decryptedByA = $this->vault->decrypt($ciphertext, $this->tenantA->id, 'fiscal_a1');
        $this->assertSame($sensitivePayload, $decryptedByA);

        // Tenant B tenta descriptografar os dados do Tenant A
        $this->expectException(CrossTenantTamperingException::class);
        $this->vault->decrypt($ciphertext, $this->tenantB->id, 'fiscal_a1');
    }

    /**
     * Valida que propósito (purpose) incorreto no cofre invalida a descriptografia.
     */
    public function test_vault_encryption_validates_purpose(): void
    {
        $secret = 'TOKEN_API_GATEWAY_TENANT_A';
        $ciphertext = $this->vault->encrypt($secret, $this->tenantA->id, 'asaas_key');

        $this->expectException(DecryptException::class);
        $this->vault->decrypt($ciphertext, $this->tenantA->id, 'stripe_key');
    }

    /**
     * Valida que sem contexto de Tenant ativo, o TenantScope opera em modo FAIL-CLOSED (força 1 = 0).
     */
    public function test_tenant_scope_fails_closed_when_context_is_empty(): void
    {
        // Cria registros no banco para os dois tenants com Landlord Bypass
        $this->context->bypass(function () {
            $p1 = new DummyProject(['name' => 'Projeto Alpha 1']);
            $p1->tenant_id = $this->tenantA->id;
            $p1->save();

            $p2 = new DummyProject(['name' => 'Projeto Beta 1']);
            $p2->tenant_id = $this->tenantB->id;
            $p2->save();
        });

        // Garante que o contexto está vazio
        $this->context->clear();
        $this->assertFalse($this->context->has());

        // A consulta DEVE retornar zero registros (Fail-Closed total)
        $projects = DummyProject::all();
        $this->assertCount(0, $projects);
        $this->assertSame(0, DummyProject::count());
    }

    /**
     * Valida que o TenantScope isola estritamente os dados do Tenant A e Tenant B.
     */
    public function test_tenant_scope_strictly_isolates_records(): void
    {
        // Popula registros sob o Tenant A
        $this->context->set($this->tenantA);
        DummyProject::create(['name' => 'Projeto Alpha 1']);
        DummyProject::create(['name' => 'Projeto Alpha 2']);

        // Popula registros sob o Tenant B
        $this->context->set($this->tenantB);
        DummyProject::create(['name' => 'Projeto Beta 1']);

        // Consulta sob o contexto do Tenant A
        $this->context->set($this->tenantA);
        $alphaProjects = DummyProject::all();
        $this->assertCount(2, $alphaProjects);
        $this->assertTrue($alphaProjects->every(fn ($p) => $p->tenant_id === $this->tenantA->id));

        // Consulta sob o contexto do Tenant B
        $this->context->set($this->tenantB);
        $betaProjects = DummyProject::all();
        $this->assertCount(1, $betaProjects);
        $this->assertSame($this->tenantB->id, $betaProjects->first()->tenant_id);
    }

    /**
     * Valida que qualquer tentativa de alterar o tenant_id no update lança CrossTenantTamperingException.
     */
    public function test_tenant_model_prevents_tenant_id_mutation(): void
    {
        $this->context->set($this->tenantA);
        $project = DummyProject::create(['name' => 'Projeto Crítico']);

        $this->expectException(CrossTenantTamperingException::class);

        // Tentativa maliciosa de migrar o projeto para a empresa B
        $project->tenant_id = $this->tenantB->id;
        $project->save();
    }

    /**
     * Valida que criar um registro sem contexto de tenant lança TenantContextMissingException.
     */
    public function test_creating_tenant_model_without_context_throws_exception(): void
    {
        $this->context->clear();

        $this->expectException(TenantContextMissingException::class);
        DummyProject::create(['name' => 'Tentativa Órfã']);
    }

    /**
     * Valida que sob Landlord Bypass todos os registros são visíveis para a administração do SaaS.
     */
    public function test_landlord_bypass_allows_cross_tenant_aggregation(): void
    {
        $this->context->set($this->tenantA);
        DummyProject::create(['name' => 'Projeto Alpha']);

        $this->context->set($this->tenantB);
        DummyProject::create(['name' => 'Projeto Beta']);

        $this->context->clear();

        // Operação de Landlord consolidada
        $totalProjects = $this->context->bypass(function () {
            return DummyProject::count();
        });

        $this->assertSame(2, $totalProjects);

        // Após o bloco bypass, o fail-closed volta a operar imediatamente
        $this->assertSame(0, DummyProject::count());
    }

    /**
     * Valida a regra de validação TenantExists contra ataques IDOR de enumeração.
     */
    public function test_tenant_exists_validation_rule(): void
    {
        $this->context->set($this->tenantA);
        $projectA = DummyProject::create(['name' => 'Projeto Alpha Validação']);

        $this->context->set($this->tenantB);
        $projectB = DummyProject::create(['name' => 'Projeto Beta Validação']);

        // No contexto do Tenant A:
        $this->context->set($this->tenantA);

        // 1. Validando projeto que pertence ao Tenant A: DEVE PASSAR
        $validatorSuccess = Validator::make(
            ['project_id' => $projectA->id],
            ['project_id' => ['required', new TenantExists('dummy_projects', 'id')]]
        );
        $this->assertFalse($validatorSuccess->fails());

        // 2. Validando ID do projeto do Tenant B (Tentativa de IDOR Cross-Tenant): DEVE FALHAR
        $validatorIdor = Validator::make(
            ['project_id' => $projectB->id],
            ['project_id' => ['required', new TenantExists('dummy_projects', 'id')]]
        );
        $this->assertTrue($validatorIdor->fails());
    }

    /**
     * Valida a integridade do helper SQL de PostgreSQL RLS.
     */
    public function test_postgres_rls_helper_generates_correct_policy(): void
    {
        $sql = PostgresRlsHelper::getCreatePolicySql('production_orders');

        $this->assertStringContainsString('CREATE POLICY tenant_isolation_policy ON production_orders', $sql);
        $this->assertStringContainsString('AS RESTRICTIVE', $sql);
        $this->assertStringContainsString("current_setting('app.current_tenant_id', true)", $sql);
    }
}
