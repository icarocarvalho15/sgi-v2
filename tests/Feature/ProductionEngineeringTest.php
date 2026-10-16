<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\Inventory\DTOs\StockMovementDTO;
use App\Modules\Inventory\Enums\KardexMovementType;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\UnitOfMeasure;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\KardexLedgerService;
use App\Modules\Production\DTOs\CreateProductionOrderDTO;
use App\Modules\Production\DTOs\RecordProductionDTO;
use App\Modules\Production\Enums\OperationStatus;
use App\Modules\Production\Enums\ProductionOrderStatus;
use App\Modules\Production\Exceptions\CircularBomDependencyException;
use App\Modules\Production\Models\Bom;
use App\Modules\Production\Models\BomItem;
use App\Modules\Production\Models\RoutingStep;
use App\Modules\Production\Models\WorkCenter;
use App\Modules\Production\Services\BomRollupService;
use App\Modules\Production\Services\ProductionOrderService;
use Database\Seeders\CoreLandlordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductionEngineeringTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantAlfa;

    private Tenant $tenantBeta;

    private User $userAlfa;

    private User $userBeta;

    private Warehouse $warehouseAlfa;

    private UnitOfMeasure $unitAlfa;

    private Item $rawMaterial1;

    private Item $rawMaterial2;

    private Item $manufacturedProduct;

    private WorkCenter $workCenterCnc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CoreLandlordSeeder::class);

        /** @var Tenant $tenantAlfa */
        $tenantAlfa = Tenant::query()->where('subdomain', 'alfa')->firstOrFail();
        $this->tenantAlfa = $tenantAlfa;

        /** @var Tenant $tenantBeta */
        $tenantBeta = Tenant::query()->where('subdomain', 'beta')->firstOrFail();
        $this->tenantBeta = $tenantBeta;

        /** @var User $userAlfa */
        $userAlfa = User::query()->where('email', 'alfa.admin@metalurgicaalfa.com.br')->firstOrFail();
        $this->userAlfa = $userAlfa;

        /** @var User $userBeta */
        $userBeta = User::query()->where('email', 'beta.admin@industriabeta.com.br')->firstOrFail();
        $this->userBeta = $userBeta;

        // Contexto Alfa
        app(TenantContext::class)->set($this->tenantAlfa);

        /** @var Warehouse $warehouseAlfa */
        $warehouseAlfa = Warehouse::query()->where('code', 'ALM-01')->firstOrFail();
        $this->warehouseAlfa = $warehouseAlfa;

        /** @var UnitOfMeasure $unitAlfa */
        $unitAlfa = UnitOfMeasure::query()->where('code', 'UN')->firstOrFail();
        $this->unitAlfa = $unitAlfa;

        // Insumos e Matérias-Primas
        $this->rawMaterial1 = Item::query()->create([
            'sku' => 'MP-CHAPA-1020',
            'name' => 'Chapa de Aço 1020 3mm',
            'unit_of_measure_id' => $this->unitAlfa->id,
            'default_warehouse_id' => $this->warehouseAlfa->id,
            'is_raw_material' => true,
            'average_cost' => '50.0000',
            'last_cost' => '50.0000',
        ]);

        $this->rawMaterial2 = Item::query()->create([
            'sku' => 'MP-PARAFUSO-M8',
            'name' => 'Parafuso Sextavado M8',
            'unit_of_measure_id' => $this->unitAlfa->id,
            'default_warehouse_id' => $this->warehouseAlfa->id,
            'is_raw_material' => true,
            'average_cost' => '2.0000',
            'last_cost' => '2.0000',
        ]);

        // Produto Manufaturado
        $this->manufacturedProduct = Item::query()->create([
            'sku' => 'PA-MESA-IND-01',
            'name' => 'Mesa Industrial 1200x800',
            'unit_of_measure_id' => $this->unitAlfa->id,
            'default_warehouse_id' => $this->warehouseAlfa->id,
            'is_manufactured' => true,
            'is_sellable' => true,
            'average_cost' => '0.0000',
        ]);

        // Centro de Trabalho
        $this->workCenterCnc = WorkCenter::query()->create([
            'code' => 'CT-CORTE',
            'name' => 'Centro de Corte a Laser',
            'hourly_cost' => '120.0000',
            'capacity_per_day_hours' => '8.00',
            'is_active' => true,
        ]);

        app(TenantContext::class)->clear();
    }

    /**
     * Valida que o Rollup de Custo da BOM calcula com precisão matemática bcmath
     * considerando percentual de perda (scrap rate) dos insumos.
     */
    public function test_bom_rollup_service_calculates_estimated_costs_with_scrap_rate(): void
    {
        app(TenantContext::class)->set($this->tenantAlfa);
        $rollupService = app(BomRollupService::class);

        /** @var Bom $bom */
        $bom = Bom::query()->create([
            'item_id' => $this->manufacturedProduct->id,
            'code' => 'BOM-MESA-V1',
            'version' => 1,
            'batch_size' => '1.0000',
        ]);

        // Componente 1: 2 unidades de Chapa @ R$ 50,00 com 10% de perda
        // Custo = 2.0 * (1 + 0.10) * 50 = 2.2 * 50 = 110.0000
        BomItem::query()->create([
            'bom_id' => $bom->id,
            'item_id' => $this->rawMaterial1->id,
            'unit_of_measure_id' => $this->unitAlfa->id,
            'quantity' => '2.0000',
            'scrap_rate_percentage' => '10.00',
        ]);

        // Componente 2: 8 unidades de Parafuso @ R$ 2,00 sem perda
        // Custo = 8 * 2 = 16.0000
        BomItem::query()->create([
            'bom_id' => $bom->id,
            'item_id' => $this->rawMaterial2->id,
            'unit_of_measure_id' => $this->unitAlfa->id,
            'quantity' => '8.0000',
            'scrap_rate_percentage' => '0.00',
        ]);

        $calculatedBom = $rollupService->calculate($bom);

        // 110.0000 + 16.0000 = 126.0000
        $this->assertSame('126.0000', (string) $calculatedBom->total_estimated_cost);
    }

    /**
     * Valida que a engenharia rejeita dependências circulares na Ficha Técnica.
     */
    public function test_bom_prevents_circular_dependencies(): void
    {
        app(TenantContext::class)->set($this->tenantAlfa);
        $rollupService = app(BomRollupService::class);

        $this->expectException(CircularBomDependencyException::class);

        // Tentar validar uma BOM onde o produto final tem a si mesmo como insumo
        $rollupService->validateNoCircularDependency(
            $this->manufacturedProduct->id,
            [$this->manufacturedProduct->id]
        );
    }

    /**
     * Valida que a liberação da OP gera atomicamente o empenho de materiais e as operações do roteiro.
     */
    public function test_production_order_release_generates_materials_and_operations(): void
    {
        app(TenantContext::class)->set($this->tenantAlfa);
        $orderService = app(ProductionOrderService::class);

        /** @var Bom $bom */
        $bom = Bom::query()->create([
            'item_id' => $this->manufacturedProduct->id,
            'code' => 'BOM-MESA-V1',
            'version' => 1,
            'batch_size' => '1.0000',
            'total_estimated_cost' => '126.0000',
        ]);

        BomItem::query()->create([
            'bom_id' => $bom->id,
            'item_id' => $this->rawMaterial1->id,
            'unit_of_measure_id' => $this->unitAlfa->id,
            'quantity' => '2.0000',
            'scrap_rate_percentage' => '10.00',
            'unit_cost' => '50.0000',
            'total_cost' => '110.0000',
        ]);

        RoutingStep::query()->create([
            'bom_id' => $bom->id,
            'work_center_id' => $this->workCenterCnc->id,
            'sequence' => 10,
            'name' => 'Corte a Laser CNC',
            'setup_time_minutes' => '15.00',
            'run_time_minutes_per_unit' => '5.00',
        ]);

        $order = $orderService->create(new CreateProductionOrderDTO(
            itemId: $this->manufacturedProduct->id,
            bomId: $bom->id,
            warehouseId: $this->warehouseAlfa->id,
            materialsWarehouseId: $this->warehouseAlfa->id,
            plannedQuantity: '5.0000',
        ));

        $this->assertSame(ProductionOrderStatus::Draft, $order->status);

        $releasedOrder = $orderService->release($order);

        $this->assertSame(ProductionOrderStatus::Released, $releasedOrder->status);
        $this->assertCount(1, $releasedOrder->materials);
        $this->assertCount(1, $releasedOrder->operations);

        // 5 lotes * 2.2 unidades planejadas = 11.0000
        $material = $releasedOrder->materials->first();
        $this->assertSame('11.0000', (string) $material->planned_quantity);
    }

    /**
     * Valida o ciclo de vida de operações de chão de fábrica (Iniciar -> Pausar -> Concluir).
     */
    public function test_shop_floor_operations_lifecycle(): void
    {
        app(TenantContext::class)->set($this->tenantAlfa);
        $orderService = app(ProductionOrderService::class);

        /** @var Bom $bom */
        $bom = Bom::query()->create([
            'item_id' => $this->manufacturedProduct->id,
            'code' => 'BOM-MESA-V1',
            'version' => 1,
            'batch_size' => '1.0000',
        ]);

        RoutingStep::query()->create([
            'bom_id' => $bom->id,
            'work_center_id' => $this->workCenterCnc->id,
            'sequence' => 10,
            'name' => 'Corte a Laser CNC',
        ]);

        $order = $orderService->create(new CreateProductionOrderDTO(
            itemId: $this->manufacturedProduct->id,
            bomId: $bom->id,
            warehouseId: $this->warehouseAlfa->id,
            materialsWarehouseId: $this->warehouseAlfa->id,
            plannedQuantity: '1.0000',
        ));

        $order = $orderService->release($order);
        $operation = $order->operations->first();

        // Inicia Operação
        $opStarted = $orderService->startOperation($operation, $this->userAlfa->id);
        $this->assertSame(OperationStatus::InProgress, $opStarted->status);
        $this->assertSame(ProductionOrderStatus::InProgress, $order->fresh()->status);

        // Pausa Operação
        $opPaused = $orderService->pauseOperation($opStarted);
        $this->assertSame(OperationStatus::Paused, $opPaused->status);

        // Conclui Operação
        $opCompleted = $orderService->completeOperation($opPaused);
        $this->assertSame(OperationStatus::Completed, $opCompleted->status);
    }

    /**
     * Valida que o apontamento de produção realiza baixa proporcional de matéria-prima no Kardex
     * e dá entrada no produto acabado com custo unitário real apurado.
     */
    public function test_record_production_consumes_materials_and_credits_finished_goods_to_kardex(): void
    {
        app(TenantContext::class)->set($this->tenantAlfa);
        $kardexService = app(KardexLedgerService::class);
        $orderService = app(ProductionOrderService::class);

        // 1. Abastece estoque inicial de matéria-prima: 20 unidades @ R$ 50,00 cada
        $kardexService->record(new StockMovementDTO(
            warehouseId: $this->warehouseAlfa->id,
            itemId: $this->rawMaterial1->id,
            movementType: KardexMovementType::InPurchase,
            quantity: '20.0000',
            unitCost: '50.0000',
            documentNumber: 'NF-INITIAL-RAW',
            userId: $this->userAlfa->id
        ));

        // 2. Cria BOM e OP de 5 mesas
        /** @var Bom $bom */
        $bom = Bom::query()->create([
            'item_id' => $this->manufacturedProduct->id,
            'code' => 'BOM-MESA-V1',
            'version' => 1,
            'batch_size' => '1.0000',
        ]);

        // Consome 2 chapas por mesa (sem perda)
        BomItem::query()->create([
            'bom_id' => $bom->id,
            'item_id' => $this->rawMaterial1->id,
            'unit_of_measure_id' => $this->unitAlfa->id,
            'quantity' => '2.0000',
            'scrap_rate_percentage' => '0.00',
            'unit_cost' => '50.0000',
            'total_cost' => '100.0000',
        ]);

        $order = $orderService->create(new CreateProductionOrderDTO(
            itemId: $this->manufacturedProduct->id,
            bomId: $bom->id,
            warehouseId: $this->warehouseAlfa->id,
            materialsWarehouseId: $this->warehouseAlfa->id,
            plannedQuantity: '5.0000',
        ));

        $order = $orderService->release($order);

        // 3. Aponta produção total de 5 mesas
        $completedOrder = $orderService->recordProduction(new RecordProductionDTO(
            productionOrderId: $order->id,
            producedQuantity: '5.0000',
            scrappedQuantity: '0.0000',
            userId: $this->userAlfa->id,
        ));

        // Valida status e saldos da OP
        $this->assertSame(ProductionOrderStatus::Completed, $completedOrder->status);
        $this->assertSame('5.0000', (string) $completedOrder->produced_quantity);

        // 4. Valida Kardex: Chapa consumiu 10 unidades (2 * 5)
        $rawBalance = InventoryBalance::query()
            ->where('warehouse_id', $this->warehouseAlfa->id)
            ->where('item_id', $this->rawMaterial1->id)
            ->firstOrFail();

        // 20 inicial - 10 consumido = 10 restante
        $this->assertSame('10.0000', (string) $rawBalance->quantity_on_hand);

        // 5. Valida Kardex: Entrada de 5 mesas prontas
        $finishedBalance = InventoryBalance::query()
            ->where('warehouse_id', $this->warehouseAlfa->id)
            ->where('item_id', $this->manufacturedProduct->id)
            ->firstOrFail();

        $this->assertSame('5.0000', (string) $finishedBalance->quantity_on_hand);
        // Custo apurado: 10 chapas @ 50 = R$ 500 total / 5 mesas = R$ 100 por mesa
        $this->assertSame('100.0000', (string) $finishedBalance->average_cost);
    }

    /**
     * Valida isolamento Cross-Tenant rigoroso (Zero-Trust) em BOMs, OPs e Chão de Fábrica.
     */
    public function test_tenant_beta_cannot_access_or_tamper_with_tenant_alfa_production(): void
    {
        // 1. Cria BOM e OP na empresa Alfa
        app(TenantContext::class)->set($this->tenantAlfa);
        $orderService = app(ProductionOrderService::class);

        /** @var Bom $bomAlfa */
        $bomAlfa = Bom::query()->create([
            'item_id' => $this->manufacturedProduct->id,
            'code' => 'BOM-ALFA-SECRET',
            'version' => 1,
            'batch_size' => '1.0000',
        ]);

        $orderAlfa = $orderService->create(new CreateProductionOrderDTO(
            itemId: $this->manufacturedProduct->id,
            bomId: $bomAlfa->id,
            warehouseId: $this->warehouseAlfa->id,
            materialsWarehouseId: $this->warehouseAlfa->id,
            plannedQuantity: '10.0000',
        ));

        app(TenantContext::class)->clear();

        // 2. Atua como Tenant Beta
        $response = $this->actingAs($this->userBeta)
            ->withSession(['current_tenant_id' => $this->tenantBeta->id])
            ->get('/app/production/orders');

        $response->assertOk();
        // Não deve listar a OP da Alfa
        $response->assertDontSee($orderAlfa->order_number);

        // 3. Tenta liberar a OP da Alfa via requisição da Beta
        $releaseResponse = $this->actingAs($this->userBeta)
            ->withSession(['current_tenant_id' => $this->tenantBeta->id])
            ->post("/app/production/orders/{$orderAlfa->id}/release");

        // Deve falhar com 404 (graças ao TenantScope/RLS que oculta o ID da Alfa)
        $releaseResponse->assertNotFound();
    }
}
