<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\Inventory\DTOs\StockMovementDTO;
use App\Modules\Inventory\Enums\KardexMovementType;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\KardexMovement;
use App\Modules\Inventory\Models\UnitOfMeasure;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\KardexLedgerService;
use Database\Seeders\CoreLandlordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

final class InventoryKardexTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantAlfa;

    private Tenant $tenantBeta;

    private User $userAlfa;

    private User $userBeta;

    private Warehouse $warehouseAlfa;

    private UnitOfMeasure $unitAlfa;

    private Item $itemAlfa;

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

        /** @var Item $itemAlfa */
        $itemAlfa = Item::query()->create([
            'sku' => 'MP-CHAPA-1020',
            'name' => 'Chapa de Aço 1020 3mm',
            'unit_of_measure_id' => $this->unitAlfa->id,
            'default_warehouse_id' => $this->warehouseAlfa->id,
            'is_raw_material' => true,
            'allow_negative_stock' => false,
            'min_stock' => '5.0000',
            'average_cost' => '0.0000',
        ]);
        $this->itemAlfa = $itemAlfa;

        app(TenantContext::class)->clear();
    }

    /**
     * Valida que movimentações no Kardex calculam com precisão o Custo Médio Ponderado (CMP)
     * e atualizam de forma atômica o saldo físico em inventory_balances.
     */
    public function test_kardex_inbound_calculates_weighted_average_cost_accurately(): void
    {
        app(TenantContext::class)->set($this->tenantAlfa);
        $service = app(KardexLedgerService::class);

        // 1ª Entrada: 10 unidades @ R$ 50,00 cada
        $firstMovement = $service->record(new StockMovementDTO(
            warehouseId: $this->warehouseAlfa->id,
            itemId: $this->itemAlfa->id,
            movementType: KardexMovementType::InPurchase,
            quantity: '10.0000',
            unitCost: '50.0000',
            documentNumber: 'NF-1001',
            userId: $this->userAlfa->id
        ));

        $this->assertSame('10.0000', $firstMovement->resulting_balance);
        $this->assertSame('50.0000', $firstMovement->resulting_average_cost);

        // Verifica snapshot
        $balance = InventoryBalance::query()
            ->where('warehouse_id', $this->warehouseAlfa->id)
            ->where('item_id', $this->itemAlfa->id)
            ->firstOrFail();

        $this->assertSame('10.0000', $balance->quantity_on_hand);
        $this->assertSame('50.0000', $balance->average_cost);

        // 2ª Entrada: 10 unidades @ R$ 100,00 cada
        // Novo CMP esperado: (10 * 50 + 10 * 100) / 20 = 1500 / 20 = 75,0000
        $secondMovement = $service->record(new StockMovementDTO(
            warehouseId: $this->warehouseAlfa->id,
            itemId: $this->itemAlfa->id,
            movementType: KardexMovementType::InPurchase,
            quantity: '10.0000',
            unitCost: '100.0000',
            documentNumber: 'NF-1002',
            userId: $this->userAlfa->id
        ));

        $this->assertSame('20.0000', $secondMovement->resulting_balance);
        $this->assertSame('75.0000', $secondMovement->resulting_average_cost);

        $balance->refresh();
        $this->assertSame('20.0000', $balance->quantity_on_hand);
        $this->assertSame('75.0000', $balance->average_cost);

        // Verifica atualização no cadastro mestre
        $this->itemAlfa->refresh();
        $this->assertSame('75.0000', $this->itemAlfa->average_cost);
        $this->assertSame('100.0000', $this->itemAlfa->last_cost);
    }

    /**
     * Valida bloqueio estrito contra estoque negativo quando allow_negative_stock = false.
     */
    public function test_negative_stock_is_strictly_blocked_when_disallowed(): void
    {
        app(TenantContext::class)->set($this->tenantAlfa);
        $service = app(KardexLedgerService::class);

        // Entrada inicial de 5 unidades
        $service->record(new StockMovementDTO(
            warehouseId: $this->warehouseAlfa->id,
            itemId: $this->itemAlfa->id,
            movementType: KardexMovementType::InPurchase,
            quantity: '5.0000',
            unitCost: '30.0000',
            userId: $this->userAlfa->id
        ));

        // Tentativa de saída de 6 unidades (saldo insuficiente)
        $this->expectException(InsufficientStockException::class);

        $service->record(new StockMovementDTO(
            warehouseId: $this->warehouseAlfa->id,
            itemId: $this->itemAlfa->id,
            movementType: KardexMovementType::OutProduction,
            quantity: '6.0000',
            userId: $this->userAlfa->id
        ));
    }

    /**
     * Valida que movimentações do Kardex são imutáveis (lançam LogicException se alteradas ou deletadas).
     */
    public function test_kardex_movements_are_strictly_immutable(): void
    {
        app(TenantContext::class)->set($this->tenantAlfa);
        $service = app(KardexLedgerService::class);

        $movement = $service->record(new StockMovementDTO(
            warehouseId: $this->warehouseAlfa->id,
            itemId: $this->itemAlfa->id,
            movementType: KardexMovementType::InPurchase,
            quantity: '10.0000',
            unitCost: '20.0000',
            userId: $this->userAlfa->id
        ));

        // Tentativa de UPDATE deve falhar
        $this->expectException(LogicException::class);
        $movement->update(['quantity' => '99.0000']);
    }

    /**
     * Valida que o endpoint de ajuste manual de estoque exige justificativa
     * e registra a movimentação de forma correta.
     */
    public function test_stock_adjustment_requires_mandatory_justification_and_updates_kardex(): void
    {
        $response = $this->actingAs($this->userAlfa)
            ->withSession(['active_tenant_id' => $this->tenantAlfa->id])
            ->post('/app/inventory/adjustments', [
                'warehouse_id' => $this->warehouseAlfa->id,
                'item_id' => $this->itemAlfa->id,
                'direction' => 'IN',
                'quantity' => '15.5000',
                'unit_cost' => '42.0000',
                'justification' => 'Ajuste de inventário físico anual com contagem cega',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        app(TenantContext::class)->set($this->tenantAlfa);

        $movement = KardexMovement::query()
            ->where('item_id', $this->itemAlfa->id)
            ->where('movement_type', KardexMovementType::InAdjustment)
            ->firstOrFail();

        $this->assertSame('15.5000', $movement->quantity);
        $this->assertSame('Ajuste de inventário físico anual com contagem cega', $movement->justification);
        $this->assertSame($this->userAlfa->id, $movement->user_id);
    }

    /**
     * Valida isolamento Cross-Tenant total:
     * O Tenant Beta JAMAIS enxerga os itens, armazéns ou extrato Kardex do Tenant Alfa.
     */
    public function test_tenant_beta_cannot_view_or_access_tenant_alfa_inventory(): void
    {
        // Tenant Alfa cria item e lança movimento
        app(TenantContext::class)->set($this->tenantAlfa);
        $service = app(KardexLedgerService::class);
        $service->record(new StockMovementDTO(
            warehouseId: $this->warehouseAlfa->id,
            itemId: $this->itemAlfa->id,
            movementType: KardexMovementType::InPurchase,
            quantity: '10.0000',
            unitCost: '10.0000',
            userId: $this->userAlfa->id
        ));

        // Tenant Beta acessa o catálogo e extrato via HTTP
        $responseItems = $this->actingAs($this->userBeta)
            ->withSession(['active_tenant_id' => $this->tenantBeta->id])
            ->get('/app/inventory/items');

        $responseItems->assertOk();
        // Não deve conter o SKU do Tenant Alfa
        $responseItems->assertDontSee('MP-CHAPA-1020');

        // Via Model com TenantScope ativo para Beta
        app(TenantContext::class)->set($this->tenantBeta);

        $betaItems = Item::query()->where('sku', 'MP-CHAPA-1020')->get();
        $this->assertCount(0, $betaItems);

        $betaMovements = KardexMovement::query()->where('item_id', $this->itemAlfa->id)->get();
        $this->assertCount(0, $betaMovements);
    }

    /**
     * Valida prevenção contra enumeração/injeção Cross-Tenant:
     * O Tenant Beta NÃO consegue cadastrar um item referenciando a Unidade de Medida
     * ou Almoxarifado do Tenant Alfa (validação com regra TenantExists).
     */
    public function test_tenant_cannot_create_item_with_another_tenants_warehouse_or_unit(): void
    {
        $response = $this->actingAs($this->userBeta)
            ->withSession(['active_tenant_id' => $this->tenantBeta->id])
            ->post('/app/inventory/items', [
                'sku' => 'ITEM-BETA-HACK',
                'name' => 'Item Tentando Invasão',
                'unit_of_measure_id' => $this->unitAlfa->id, // Unidade pertencente ao Tenant Alfa!
                'default_warehouse_id' => $this->warehouseAlfa->id, // Depósito pertencente ao Tenant Alfa!
                'is_sellable' => true,
            ]);

        $response->assertSessionHasErrors(['unit_of_measure_id', 'default_warehouse_id']);
    }
}
