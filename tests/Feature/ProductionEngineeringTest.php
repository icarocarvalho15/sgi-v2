<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\CoreAdmin\Services\DefaultStatusesSeederService;
use App\Modules\Production\Exceptions\BomCircularReferenceException;
use App\Modules\Production\Models\Bom;
use App\Modules\Production\Models\BomItem;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Production\Models\WorkCenter;
use App\Modules\Production\Services\ProductionOrderService;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductionEngineeringTest extends TestCase
{
    private Tenant $tenantA;
    private Tenant $tenantB;
    private WorkCenter $heidelbergOffset;
    private TenantStatus $initialProdStatus;
    private TenantStatus $printingProdStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Industrial Alpha',
            'subdomain' => 'alpha-ind-' . Str::random(6),
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Concorrente Beta',
            'subdomain' => 'beta-ind-' . Str::random(6),
            'status' => 'active',
        ]);

        TenantContext::set($this->tenantA);
        DefaultStatusesSeederService::seedForTenant($this->tenantA->id);

        $this->initialProdStatus = TenantStatus::forModule('production_orders')
            ->where('slug', 'pre-impressao')
            ->firstOrFail();

        $this->printingProdStatus = TenantStatus::forModule('production_orders')
            ->where('slug', 'em-impressao')
            ->firstOrFail();

        $this->heidelbergOffset = WorkCenter::create([
            'name' => 'Heidelberg Speedmaster XL 75',
            'code' => 'OFF-01',
            'type' => 'offset_printing',
            'hourly_rate' => '180.0000',
            'capacity_per_hour' => '8000.0000',
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_bom_circular_dependency_is_strictly_blocked(): void
    {
        // 1. Criar Produto A (ex: Livro Capa Dura Completo)
        $bomA = Bom::create([
            'code' => 'BOM-LIV-001',
            'name' => 'Livro Capa Dura 200 Páginas',
            'product_type' => 'commercial_print',
        ]);

        // 2. Criar Subconjunto B (ex: Miolo Costurado do Livro)
        $bomB = Bom::create([
            'code' => 'BOM-MIO-001',
            'name' => 'Miolo do Livro 200 Páginas',
            'product_type' => 'sub_assembly',
        ]);

        // Produto A usa Subconjunto B
        BomItem::create([
            'bom_id' => $bomA->id,
            'item_type' => 'sub_assembly',
            'name' => 'Miolo Impresso',
            'child_bom_id' => $bomB->id,
            'quantity' => '1.0000',
        ]);

        // Prova de Integridade Industrial:
        // Tentar inserir o Produto A como insumo do Subconjunto B (Ciclo A -> B -> A)
        $this->expectException(BomCircularReferenceException::class);

        BomItem::create([
            'bom_id' => $bomB->id,
            'item_type' => 'sub_assembly',
            'name' => 'Tentativa Circular Maliciosa',
            'child_bom_id' => $bomA->id,
            'quantity' => '1.0000',
        ]);
    }

    public function test_it_allocates_materials_from_bom_with_scrap_factor_on_production_order(): void
    {
        $bom = Bom::create([
            'code' => 'BOM-CARTAZ-01',
            'name' => 'Cartaz A3 Couché 150g',
            'product_type' => 'commercial_print',
        ]);

        // Insumo 1: Papel Couché (1 folha por cartaz + 10% de quebra técnica de acerto)
        BomItem::create([
            'bom_id' => $bom->id,
            'item_type' => 'substrate',
            'name' => 'Papel Couché Fosco 150g',
            'quantity' => '1.0000',
            'unit_of_measure' => 'FL',
            'scrap_factor_percent' => '10.0000',
            'work_center_id' => $this->heidelbergOffset->id,
        ]);

        // Insumo 2: Chapas CTP (4 chapas fixas para o trabalho, sem quebra)
        BomItem::create([
            'bom_id' => $bom->id,
            'item_type' => 'ctp_plate',
            'name' => 'Chapa CTP Térmica 0.30mm',
            'quantity' => '0.0040', // 4 chapas para 1.000 un
            'unit_of_measure' => 'UN',
            'scrap_factor_percent' => '0.0000',
        ]);

        // Criar OP de 1.000 cartazes
        $order = ProductionOrder::create([
            'order_number' => 'OP-2026-0099',
            'bom_id' => $bom->id,
            'status_id' => $this->initialProdStatus->id,
            'product_name' => $bom->name,
            'target_quantity' => 1000,
        ]);

        // Alocar materiais da BOM
        $materials = ProductionOrderService::allocateMaterialsFromBom($order);

        $this->assertCount(2, $materials);

        // Papel planejado: 1.000 * 1 * (1 + 0.10) = 1.100 folhas
        $paper = collect($materials)->firstWhere('item_type', 'substrate');
        $this->assertEquals('1100.0000', $paper->planned_quantity);
        $this->assertEquals('FL', $paper->unit_of_measure);
        $this->assertEquals('reserved', $paper->status);

        // Chapas CTP planejadas: 1.000 * 0.0040 = 4 chapas
        $ctp = collect($materials)->firstWhere('item_type', 'ctp_plate');
        $this->assertEquals('4.0000', $ctp->planned_quantity);
    }

    public function test_production_order_status_progression_and_shop_floor_time_logging(): void
    {
        $order = ProductionOrder::create([
            'order_number' => 'OP-2026-SHOP01',
            'status_id' => $this->initialProdStatus->id,
            'product_name' => 'Revista Institucional 32 Páginas',
            'target_quantity' => 2000,
            'produced_quantity' => 0,
        ]);

        // 1. Avançar status no chão de fábrica (para "em-impressao")
        $updatedOrder = ProductionOrderService::updateStatus($order, $this->printingProdStatus->id);
        $this->assertEquals($this->printingProdStatus->id, $updatedOrder->status_id);
        $this->assertEquals('em-impressao', $updatedOrder->status->slug);

        // 2. Apontamento de chão de fábrica pelo operador
        $log = ProductionOrderService::logOperation($order, [
            'work_center_id' => $this->heidelbergOffset->id,
            'operation_name' => 'Tiragem Offset Heidelberg',
            'started_at' => now()->subHours(2),
            'ended_at' => now(),
            'good_quantity' => 1200,
            'scrap_quantity' => 45,
            'notes' => 'Acerto inicial com 45 folhas de refugo técnico.',
        ]);

        $this->assertNotEmpty($log->id);
        $this->assertEquals(1200, $log->good_quantity);
        $this->assertEquals(45, $log->scrap_quantity);

        // Verifica incremento da quantidade produzida na OP
        $order->refresh();
        $this->assertEquals(1200, $order->produced_quantity);
    }

    public function test_cross_tenant_isolation_in_production_module(): void
    {
        // WorkCenter no Tenant A
        $wcA = $this->heidelbergOffset;

        // WorkCenter no Tenant B
        TenantContext::set($this->tenantB);
        $wcB = WorkCenter::create([
            'name' => 'Plotter Digital Roland Versa',
            'code' => 'DIG-01',
            'type' => 'digital_printing',
        ]);

        // Consulta em Tenant B não deve listar Tenant A
        $workCentersInB = WorkCenter::all();
        $this->assertFalse($workCentersInB->contains('id', $wcA->id));
        $this->assertTrue($workCentersInB->contains('id', $wcB->id));

        // Consulta em Tenant A não deve listar Tenant B
        TenantContext::set($this->tenantA);
        $workCentersInA = WorkCenter::all();
        $this->assertTrue($workCentersInA->contains('id', $wcA->id));
        $this->assertFalse($workCentersInA->contains('id', $wcB->id));
    }
}
