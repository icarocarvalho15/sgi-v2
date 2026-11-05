<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\CentralAdmin\Models\ImpersonationLog;
use App\Modules\CentralAdmin\Services\ImpersonationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    private Tenant $targetTenant;
    private User $superAdmin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->targetTenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Cliente com Dúvida de Suporte',
            'subdomain' => 'suporte-' . Str::random(6),
            'status' => 'active',
        ]);

        $this->superAdmin = User::forceCreate([
            'id' => (string) Str::uuid(),
            'name' => 'Tech Support Lead',
            'email' => 'support-' . Str::random(6) . '@sgi-erp.com',
            'password' => 'secret',
            'is_super_admin' => true,
        ]);

        $this->regularUser = User::forceCreate([
            'id' => (string) Str::uuid(),
            'name' => 'Operador Comum',
            'email' => 'user-' . Str::random(6) . '@cliente.com',
            'password' => 'secret',
            'is_super_admin' => false,
        ]);
    }

    protected function tearDown(): void
    {
        ImpersonationService::stop();
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_superadmin_can_start_and_stop_audited_impersonation_session(): void
    {
        $reason = 'Chamado #4892: Auxílio na parametrização de chapas CTP Heidelberg';
        $ip = '192.168.1.100';

        $log = ImpersonationService::start($this->superAdmin, $this->targetTenant, $reason, $ip);

        $this->assertNotEmpty($log->id);
        $this->assertEquals($this->superAdmin->id, $log->impersonator_id);
        $this->assertEquals($this->targetTenant->id, $log->tenant_id);
        $this->assertEquals($reason, $log->reason);
        $this->assertNotNull($log->started_at);
        $this->assertNull($log->ended_at);

        // Verifica se o TenantContext foi sincronizado
        $this->assertEquals($this->targetTenant->id, TenantContext::id());
        $this->assertTrue(ImpersonationService::isImpersonating());

        // Encerra sessão
        $endedLog = ImpersonationService::stop();
        $this->assertNotNull($endedLog->ended_at);
        $this->assertFalse(ImpersonationService::isImpersonating());
        $this->assertFalse(TenantContext::check());
    }

    public function test_regular_user_cannot_impersonate(): void
    {
        $this->expectException(AuthorizationException::class);

        ImpersonationService::start(
            $this->regularUser,
            $this->targetTenant,
            'Tentativa não autorizada'
        );
    }

    public function test_impersonation_requires_mandatory_audit_reason(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ImpersonationService::start(
            $this->superAdmin,
            $this->targetTenant,
            '   ' // Justificativa em branco
        );
    }
}
