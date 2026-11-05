<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Modules\CRM\Models\Partner;
use App\Modules\CRM\Models\PartnerAddress;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmPartnersTest extends TestCase
{
    private Tenant $tenantA;
    private Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Alpha Express',
            'subdomain' => 'alpha-' . Str::random(6),
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Indústria Brindes Beta',
            'subdomain' => 'beta-' . Str::random(6),
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_it_creates_partner_with_multiple_addresses_and_valid_cnpj(): void
    {
        TenantContext::set($this->tenantA);

        // Valid CNPJ: 11.222.333/0001-81
        $partner = Partner::create([
            'type' => 'customer',
            'person_type' => 'company',
            'name' => 'Agência Publicidade Criativa Ltda',
            'trade_name' => 'Criativa Ads',
            'document_number' => '11222333000181',
            'email' => 'contato@criativa.com.br',
            'phone' => '11999998888',
        ]);

        $partner->addresses()->create([
            'type' => 'billing',
            'postal_code' => '01310-100',
            'street' => 'Avenida Paulista',
            'number' => '1000',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
            'is_default' => true,
        ]);

        $partner->addresses()->create([
            'type' => 'shipping',
            'postal_code' => '04543-000',
            'street' => 'Avenida Faria Lima',
            'number' => '2000',
            'neighborhood' => 'Itaim Bibi',
            'city' => 'São Paulo',
            'state' => 'SP',
            'is_default' => false,
        ]);

        $this->assertEquals($this->tenantA->id, $partner->tenant_id);
        $this->assertCount(2, $partner->addresses);
        $this->assertEquals($this->tenantA->id, $partner->addresses->first()->tenant_id);
    }

    public function test_cross_tenant_isolation_on_partners(): void
    {
        TenantContext::set($this->tenantA);
        $partnerA = Partner::create([
            'type' => 'customer',
            'name' => 'Cliente Exclusivo Alpha',
            'document_number' => '11222333000181',
        ]);

        TenantContext::set($this->tenantB);
        $partnerB = Partner::create([
            'type' => 'customer',
            'name' => 'Cliente Exclusivo Beta',
            'document_number' => '22333444000192',
        ]);

        // While in Tenant B context, querying partners must not see Tenant A
        $partnersInB = Partner::all();
        $this->assertFalse($partnersInB->contains('id', $partnerA->id));
        $this->assertTrue($partnersInB->contains('id', $partnerB->id));

        // Switch back to Tenant A
        TenantContext::set($this->tenantA);
        $partnersInA = Partner::all();
        $this->assertTrue($partnersInA->contains('id', $partnerA->id));
        $this->assertFalse($partnersInA->contains('id', $partnerB->id));
    }

    public function test_same_cnpj_can_exist_in_different_tenants_due_to_composite_unique_key(): void
    {
        // CNPJ 11.222.333/0001-81 in Tenant A
        TenantContext::set($this->tenantA);
        $partnerA = Partner::create([
            'type' => 'customer',
            'name' => 'Cliente Multi-Tenant A',
            'document_number' => '11222333000181',
        ]);

        // Same CNPJ 11.222.333/0001-81 in Tenant B
        TenantContext::set($this->tenantB);
        $partnerB = Partner::create([
            'type' => 'customer',
            'name' => 'Cliente Multi-Tenant B',
            'document_number' => '11222333000181',
        ]);

        $this->assertNotEmpty($partnerA->id);
        $this->assertNotEmpty($partnerB->id);
        $this->assertNotEquals($partnerA->id, $partnerB->id);
    }
}
