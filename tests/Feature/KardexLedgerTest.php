<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\CoreAdmin\Services\DefaultStatusesSeederService;
use App\Modules\Inventory\Actions\ConsumeProductionOrderMaterialsAction;
use App\Modules\Inventory\Actions\ReceivePurchasedItemsAction;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\ItemCategory;
use App\Modules\Inventory\Models\KardexMovement;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\KardexLedgerService;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Production\Models\ProductionOrderMaterial;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class KardexLedgerTest extends TestCase
{
    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $userA;
    private ItemCategory $paperCategory;
    private Warehouse $centralWarehouse;
    private Item $couchePaper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Alpha Papéis',
            'subdomain' => 'alpha-pap-' . Str::random(6),
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Beta Insumos',
            'subdomain' => 'beta-pap-' . Str::random(6),
            'status' => 'active',
        ]);

        TenantContext::set($this->tenantA);

        $this->userA = User::create([
            'name' => 'Operador de Suprimentos Alpha',
            'email' => 'estoque@alpha-' . Str::random(5) . '.com',
            'password' => bcrypt('Password123!'),
            'tenant_id' => $this->tenantA->id,
        ]);

        $this->paperCategory = ItemCategory::create([
            'name' => 'Papéis e Substratos',
            'code' => 'PAPEL',
            'type' => 'raw_material',
        ]);

        $this->centralWarehouse = Warehouse::create([
            'name' => 'Almoxarifado Central Alpha',
            'code' => 'ALMOX-01',
            'is_default' => true,
        ]);

        $this->couchePaper = Item::create([
            'category_id' => $this->paperCategory->id,
            'code' => 'PAP-COUCHE-150',
            'name' => 'Papel Couché Brilho 150g 66x96cm',
            'type' => 'raw_material',
            'unit_of_measure' => 'FL',
            'standard_cost' => '0.0000',
        ]);
    }

    /**
     * Test 1: Imutabilidade do Kardex (Append-Only)
     */
    public function test_kardex_immutable_append_only_ledger_prevents_updates_and_deletes(): void
    {
        $service = app(KardexLedgerService::class);

        $movement = $service->recordMovement([
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'movement_type' => 'inventory_adjustment',
            'direction' => 'in',
            'quantity' => '1000.0000',
            'unit_cost' => '0.5000',
            'notes' => 'Carga inicial',
        ]);

        $this->assertDatabaseHas('kardex_movements', [
            'id' => $movement->id,
            'quantity' => '1000.0000',
            'direction' => 'in',
        ]);

        // Tentativa de update deve falhar
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Kardex movements are immutable and cannot be updated.');
        $movement->update(['notes' => 'Fraude contábil']);
    }

    /**
     * Test 1.1: Imutabilidade do Kardex (Delete)
     */
    public function test_kardex_immutable_prevents_delete(): void
    {
        $service = app(KardexLedgerService::class);

        $movement = $service->recordMovement([
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'movement_type' => 'inventory_adjustment',
            'direction' => 'in',
            'quantity' => '500.0000',
            'unit_cost' => '0.5000',
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Kardex movements are immutable and cannot be deleted.');
        $movement->delete();
    }

    /**
     * Test 2: Cálculo Rigoroso do Custo Médio Ponderado (CMP) com bcmath (Zero Float)
     */
    public function test_accurate_weighted_average_cost_cmp_calculation(): void
    {
        $service = app(KardexLedgerService::class);

        // Lote 1: Entrada de 100 unidades a R$ 10,0000 cada. Total = R$ 1.000,0000. CMP = R$ 10,0000
        $mov1 = $service->recordMovement([
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'movement_type' => 'purchase_receipt',
            'direction' => 'in',
            'quantity' => '100.0000',
            'unit_cost' => '10.0000',
            'notes' => 'Lote 1',
        ]);

        $this->assertSame('100.0000', $mov1->balance_quantity_after);
        $this->assertSame('10.0000', $mov1->balance_average_cost_after);
        $this->assertSame('1000.0000', $mov1->balance_total_value_after);

        // Lote 2: Entrada de 100 unidades a R$ 20,0000 cada. Total = R$ 2.000,0000.
        // Saldo total = 200 un, Valor total = R$ 3.000,0000. Novo CMP = 3000 / 200 = R$ 15,0000
        $mov2 = $service->recordMovement([
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'movement_type' => 'purchase_receipt',
            'direction' => 'in',
            'quantity' => '100.0000',
            'unit_cost' => '20.0000',
            'notes' => 'Lote 2',
        ]);

        $this->assertSame('200.0000', $mov2->balance_quantity_after);
        $this->assertSame('15.0000', $mov2->balance_average_cost_after);
        $this->assertSame('3000.0000', $mov2->balance_total_value_after);

        // Saída 1: Consumo de 50 unidades.
        // As saídas saem rigorosamente ao CMP atual (R$ 15,0000). Total saída = R$ 750,0000.
        // Saldo restante = 150 un. CMP permanece R$ 15,0000. Valor restante = R$ 2.250,0000.
        $mov3 = $service->recordMovement([
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'movement_type' => 'production_consumption',
            'direction' => 'out',
            'quantity' => '50.0000',
            'notes' => 'Consumo na impressora',
        ]);

        $this->assertSame('50.0000', $mov3->quantity);
        $this->assertSame('15.0000', $mov3->unit_cost);
        $this->assertSame('750.0000', $mov3->total_cost);
        $this->assertSame('150.0000', $mov3->balance_quantity_after);
        $this->assertSame('15.0000', $mov3->balance_average_cost_after);
        $this->assertSame('2250.0000', $mov3->balance_total_value_after);

        // Lote 3: Nova entrada de 50 unidades a R$ 25,0000 cada. Total entrada = R$ 1.250,0000.
        // Saldo novo = 200 un. Novo valor total = 2.250 + 1.250 = R$ 3.500,0000.
        // Novo CMP = 3500 / 200 = R$ 17,5000
        $mov4 = $service->recordMovement([
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'movement_type' => 'purchase_receipt',
            'direction' => 'in',
            'quantity' => '50.0000',
            'unit_cost' => '25.0000',
            'notes' => 'Lote 3',
        ]);

        $this->assertSame('200.0000', $mov4->balance_quantity_after);
        $this->assertSame('17.5000', $mov4->balance_average_cost_after);
        $this->assertSame('3500.0000', $mov4->balance_total_value_after);

        // Verificar o registro de saldo persistido na tabela inventory_balances
        $balance = InventoryBalance::where('item_id', $this->couchePaper->id)
            ->where('warehouse_id', $this->centralWarehouse->id)
            ->firstOrFail();

        $this->assertSame('200.0000', $balance->quantity_on_hand);
        $this->assertSame('17.5000', $balance->average_cost);
        $this->assertSame('3500.0000', $balance->total_value);
    }

    /**
     * Test 3: Extrato do Kardex concilia 100% com o saldo do almoxarifado (0.0000 divergência)
     */
    public function test_sum_of_kardex_movements_matches_inventory_balance_exactly(): void
    {
        $service = app(KardexLedgerService::class);

        // Sequência aleatória controlada de movimentações
        $service->recordMovement([
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'movement_type' => 'purchase_receipt',
            'direction' => 'in',
            'quantity' => '1000.0000',
            'unit_cost' => '1.2000',
        ]);

        $service->recordMovement([
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'movement_type' => 'production_consumption',
            'direction' => 'out',
            'quantity' => '250.0000',
        ]);

        $service->recordMovement([
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'movement_type' => 'purchase_receipt',
            'direction' => 'in',
            'quantity' => '500.0000',
            'unit_cost' => '1.4000',
        ]);

        $service->recordMovement([
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'movement_type' => 'production_consumption',
            'direction' => 'out',
            'quantity' => '125.0000',
        ]);

        // Cálculo acumulado via Kardex
        $inSum = KardexMovement::where('item_id', $this->couchePaper->id)
            ->where('warehouse_id', $this->centralWarehouse->id)
            ->where('direction', 'in')
            ->sum('quantity');

        $outSum = KardexMovement::where('item_id', $this->couchePaper->id)
            ->where('warehouse_id', $this->centralWarehouse->id)
            ->where('direction', 'out')
            ->sum('quantity');

        $kardexCalculatedBalance = bcsub((string) $inSum, (string) $outSum, 4);

        $balance = InventoryBalance::where('item_id', $this->couchePaper->id)
            ->where('warehouse_id', $this->centralWarehouse->id)
            ->firstOrFail();

        $this->assertSame('1125.0000', $kardexCalculatedBalance);
        $this->assertSame('1125.0000', (string) $balance->quantity_on_hand);
        $this->assertSame(0, bccomp($kardexCalculatedBalance, (string) $balance->quantity_on_hand, 4));
    }

    /**
     * Test 4: Bloqueio contra saldo negativo com rollback atômico
     */
    public function test_insufficient_stock_exception_on_negative_balance_attempt(): void
    {
        $service = app(KardexLedgerService::class);

        $service->recordMovement([
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'movement_type' => 'purchase_receipt',
            'direction' => 'in',
            'quantity' => '50.0000',
            'unit_cost' => '10.0000',
        ]);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage('Saldo insuficiente');

        // Tentativa de consumir 50.0001 quando há apenas 50.0000
        $service->recordMovement([
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'movement_type' => 'production_consumption',
            'direction' => 'out',
            'quantity' => '50.0001',
        ]);
    }

    /**
     * Test 5: Baixa automática de insumo por Ordem de Produção
     */
    public function test_automatic_consumption_from_production_order(): void
    {
        DefaultStatusesSeederService::seedForTenant($this->tenantA->id);
        $prodStatus = TenantStatus::forModule('production_orders')->firstOrFail();

        // Saldo inicial de 500 folhas
        $receiveAction = app(ReceivePurchasedItemsAction::class);
        $receiveAction->execute(
            $this->couchePaper->id,
            $this->centralWarehouse->id,
            '500.0000',
            '2.0000',
            notes: 'Estoque inicial para produção'
        );

        // Criar OP
        $op = ProductionOrder::create([
            'order_number' => 'OP-2026-TEST',
            'product_name' => 'Catálogo 16 Páginas Couché',
            'status_id' => $prodStatus->id,
            'target_quantity' => 1000,
        ]);

        $material = ProductionOrderMaterial::create([
            'production_order_id' => $op->id,
            'name' => $this->couchePaper->name,
            'item_type' => 'raw_material',
            'planned_quantity' => '300.0000',
            'consumed_quantity' => '0.0000',
            'unit_of_measure' => 'FL',
            'status' => 'reserved',
        ]);

        // Executar a action de consumo
        $consumeAction = app(ConsumeProductionOrderMaterialsAction::class);
        $movement = $consumeAction->execute(
            $op,
            $material,
            '300.0000',
            $this->centralWarehouse->id,
            $this->userA->id
        );

        $material->refresh();
        $this->assertSame('300.0000', (string) $material->consumed_quantity);
        $this->assertSame('consumed', $material->status);

        $this->assertSame('production_consumption', $movement->movement_type);
        $this->assertSame('300.0000', $movement->quantity);
        $this->assertSame('200.0000', $movement->balance_quantity_after);

        $balance = InventoryBalance::where('item_id', $this->couchePaper->id)
            ->where('warehouse_id', $this->centralWarehouse->id)
            ->firstOrFail();

        $this->assertSame('200.0000', (string) $balance->quantity_on_hand);
    }

    /**
     * Test 6: Isolamento Estrito Cross-Tenant (RLS + Double Lock)
     */
    public function test_strict_cross_tenant_isolation_in_inventory_and_kardex(): void
    {
        $service = app(KardexLedgerService::class);

        // Movimentação no Tenant A
        $service->recordMovement([
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'movement_type' => 'purchase_receipt',
            'direction' => 'in',
            'quantity' => '1000.0000',
            'unit_cost' => '5.0000',
        ]);

        $this->assertCount(1, InventoryBalance::all());
        $this->assertCount(1, KardexMovement::all());

        // Alterna para o contexto do Tenant B
        TenantContext::set($this->tenantB);

        // Tenant B não deve ver nenhum saldo ou movimentação do Tenant A (RLS fail-closed)
        $this->assertCount(0, InventoryBalance::all());
        $this->assertCount(0, KardexMovement::all());
        $this->assertCount(0, Item::all());
        $this->assertCount(0, Warehouse::all());
    }

    /**
     * Test 7: Validação de IDOR com TenantExists no Controller de Ajustes
     */
    public function test_inventory_adjustment_controller_endpoint_with_tenant_exists_validation(): void
    {
        // No contexto do Tenant A
        $response = $this->actingAs($this->userA)->post('/inventory/adjustments', [
            'item_id' => $this->couchePaper->id,
            'warehouse_id' => $this->centralWarehouse->id,
            'direction' => 'in',
            'quantity' => '150.0000',
            'unit_cost' => '4.5000',
            'notes' => 'Ajuste manual via tela web',
        ]);

        $response->assertRedirect('/inventory/balances');

        // Restaurar contexto local após o middleware terminate() tê-lo limpado
        TenantContext::set($this->tenantA);

        $balance = InventoryBalance::where('item_id', $this->couchePaper->id)
            ->where('warehouse_id', $this->centralWarehouse->id)
            ->firstOrFail();

        $this->assertSame('150.0000', (string) $balance->quantity_on_hand);
        $this->assertSame('4.5000', (string) $balance->average_cost);

        // Agora criar um item exclusivo no Tenant B
        TenantContext::set($this->tenantB);
        $catB = ItemCategory::create(['name' => 'Cat Beta', 'code' => 'CATB']);
        $itemB = Item::create([
            'category_id' => $catB->id,
            'code' => 'BETA-ITEM',
            'name' => 'Insumo Secreto Beta',
        ]);

        // Voltar ao Tenant A e tentar injetar o item_id do Tenant B (IDOR Attack)
        TenantContext::set($this->tenantA);

        $attackResponse = $this->actingAs($this->userA)->post('/inventory/adjustments', [
            'item_id' => $itemB->id, // Pertence ao Tenant B!
            'warehouse_id' => $this->centralWarehouse->id,
            'direction' => 'in',
            'quantity' => '100.0000',
            'unit_cost' => '10.0000',
            'notes' => 'Tentativa de cross-tenant IDOR',
        ]);

        // TenantExists deve bloquear estritamente com erro de validação
        $attackResponse->assertSessionHasErrors(['item_id']);
    }
}
