<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\CoreAdmin\Services\DefaultStatusesSeederService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantStatusesTest extends TestCase
{
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Express e Brindes',
            'subdomain' => 'grafica-' . Str::random(8),
            'status' => 'active',
        ]);

        TenantContext::set($this->tenant);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_it_creates_custom_statuses_for_print_shops_with_auto_slug(): void
    {
        $status = TenantStatus::create([
            'module' => 'production_orders',
            'name' => 'Aguardando Gravação CTP',
            'color' => '#6366F1',
            'position' => 1,
        ]);

        $this->assertNotEmpty($status->id);
        $this->assertEquals($this->tenant->id, $status->tenant_id);
        $this->assertEquals('aguardando-gravacao-ctp', $status->slug);
        $this->assertEquals('#6366F1', $status->color);
        $this->assertEquals(1, $status->position);
        $this->assertFalse($status->is_default);
        $this->assertFalse($status->is_final);
    }

    public function test_it_filters_statuses_by_module_and_orders_by_position(): void
    {
        TenantStatus::create([
            'module' => 'production_orders',
            'name' => 'Acabamento e Dobra',
            'position' => 3,
        ]);

        TenantStatus::create([
            'module' => 'production_orders',
            'name' => 'Impressão Heidelberg Offset',
            'position' => 1,
        ]);

        TenantStatus::create([
            'module' => 'quotes',
            'name' => 'Orçamento Enviado',
            'position' => 1,
        ]);

        $productionStatuses = TenantStatus::forModule('production_orders')->ordered()->get();
        $this->assertCount(2, $productionStatuses);
        $this->assertEquals('Impressão Heidelberg Offset', $productionStatuses->first()->name);
        $this->assertEquals('Acabamento e Dobra', $productionStatuses->last()->name);

        $quoteStatuses = TenantStatus::forModule('quotes')->get();
        $this->assertCount(1, $quoteStatuses);
        $this->assertEquals('Orçamento Enviado', $quoteStatuses->first()->name);
    }

    public function test_mark_as_default_unsets_previous_default_within_the_same_module(): void
    {
        $status1 = TenantStatus::create([
            'module' => 'quotes',
            'name' => 'Rascunho Inicial',
            'is_default' => true,
        ]);

        $status2 = TenantStatus::create([
            'module' => 'quotes',
            'name' => 'Aguardando Avaliação Técnica',
            'is_default' => false,
        ]);

        $this->assertTrue($status1->fresh()->is_default);
        $this->assertFalse($status2->fresh()->is_default);

        // Change default to status2
        $status2->markAsDefault();

        $this->assertFalse($status1->fresh()->is_default);
        $this->assertTrue($status2->fresh()->is_default);
    }

    public function test_database_enforces_unique_slug_per_tenant_and_module(): void
    {
        TenantStatus::create([
            'module' => 'quotes',
            'name' => 'Em Análise',
            'slug' => 'em-analise',
        ]);

        $this->expectException(QueryException::class);

        // Attempt duplicate slug in the same module and tenant
        TenantStatus::create([
            'module' => 'quotes',
            'name' => 'Em Análise Duplicado',
            'slug' => 'em-analise',
        ]);
    }

    public function test_it_seeds_default_industry_statuses_for_new_tenant(): void
    {
        DefaultStatusesSeederService::seedForTenant($this->tenant->id);

        TenantContext::set($this->tenant);

        // Verify quotes module statuses
        $quoteStatuses = TenantStatus::forModule('quotes')->get();
        $this->assertCount(4, $quoteStatuses);
        $this->assertTrue($quoteStatuses->contains('slug', 'rascunho-tecnico'));
        $this->assertTrue($quoteStatuses->contains('slug', 'orcamento-aprovado'));

        // Verify production orders statuses (CTP, Printing, Gifts, Finishing, Shipping)
        $prodStatuses = TenantStatus::forModule('production_orders')->get();
        $this->assertCount(7, $prodStatuses);
        $this->assertTrue($prodStatuses->contains('slug', 'pre-impressao'));
        $this->assertTrue($prodStatuses->contains('slug', 'gravacao-ctp'));
        $this->assertTrue($prodStatuses->contains('slug', 'em-impressao'));
        $this->assertTrue($prodStatuses->contains('slug', 'personalizacao-brindes'));
        $this->assertTrue($prodStatuses->contains('slug', 'acabamento-guilhotina'));
        $this->assertTrue($prodStatuses->contains('slug', 'pronto-expedicao'));

        // Verify sales kanban pipeline
        $kanbanStatuses = TenantStatus::forModule('sales_kanban')->get();
        $this->assertCount(6, $kanbanStatuses);
        $this->assertTrue($kanbanStatuses->contains('slug', 'lead-contato'));
        $this->assertTrue($kanbanStatuses->contains('slug', 'venda-fechada'));
    }
}
