<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\CRM\Enums\DealPriority;
use App\Modules\CRM\Enums\DealStatus;
use App\Modules\CRM\Enums\PartnerType;
use App\Modules\CRM\Enums\PersonType;
use App\Modules\CRM\Enums\QuoteStatus;
use App\Modules\CRM\Enums\SalesOrderStatus;
use App\Modules\CRM\Models\CrmDeal;
use App\Modules\CRM\Models\CrmDealStage;
use App\Modules\CRM\Models\CrmPipeline;
use App\Modules\CRM\Models\Partner;
use App\Modules\CRM\Models\Quote;
use App\Modules\CRM\Models\QuoteItem;
use App\Modules\CRM\Models\SalesOrder;
use App\Modules\CRM\Services\QuotePricingService;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\UnitOfMeasure;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Production\Models\Bom;
use App\Modules\Production\Models\BomItem;
use App\Modules\Production\Models\RoutingStep;
use App\Modules\Production\Models\WorkCenter;
use Database\Seeders\CoreLandlordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CrmSalesWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantAlfa;

    private Tenant $tenantBeta;

    private User $userAlfa;

    private User $userBeta;

    private Warehouse $warehouseAlfa;

    private UnitOfMeasure $unitAlfa;

    private Item $rawMaterial;

    private Item $manufacturedProduct;

    private WorkCenter $workCenter;

    private Bom $bom;

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
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("SELECT set_config('app.current_tenant_id', ?, false)", [$this->tenantAlfa->id]);
        }

        /** @var Warehouse $warehouseAlfa */
        $warehouseAlfa = Warehouse::query()->where('code', 'ALM-01')->firstOrFail();
        $this->warehouseAlfa = $warehouseAlfa;

        /** @var UnitOfMeasure $unitAlfa */
        $unitAlfa = UnitOfMeasure::query()->where('code', 'UN')->firstOrFail();
        $this->unitAlfa = $unitAlfa;

        $this->rawMaterial = Item::query()->create([
            'sku' => 'MP-ACO-01',
            'name' => 'Barra de Aço 1020',
            'unit_of_measure_id' => $this->unitAlfa->id,
            'default_warehouse_id' => $this->warehouseAlfa->id,
            'is_raw_material' => true,
            'average_cost' => '100.0000',
            'last_cost' => '100.0000',
        ]);

        $this->manufacturedProduct = Item::query()->create([
            'sku' => 'PA-VALVULA-01',
            'name' => 'Válvula Esférica Industrial',
            'unit_of_measure_id' => $this->unitAlfa->id,
            'default_warehouse_id' => $this->warehouseAlfa->id,
            'is_manufactured' => true,
            'is_sellable' => true,
        ]);

        $this->workCenter = WorkCenter::query()->create([
            'code' => 'CT-USIN',
            'name' => 'Centro de Usinagem CNC',
            'hourly_cost' => '60.0000',
            'capacity_per_day_hours' => '8.00',
        ]);

        $this->bom = Bom::query()->create([
            'item_id' => $this->manufacturedProduct->id,
            'code' => 'BOM-VALV-01',
            'version' => 1,
            'batch_size' => '1.0000',
            'total_estimated_cost' => '150.0000',
            'is_active' => true,
            'is_default' => true,
        ]);

        BomItem::query()->create([
            'bom_id' => $this->bom->id,
            'item_id' => $this->rawMaterial->id,
            'unit_of_measure_id' => $this->unitAlfa->id,
            'quantity' => '1.0000',
            'scrap_rate_percentage' => '0.00',
            'unit_cost' => '100.0000',
            'total_cost' => '100.0000',
        ]);

        RoutingStep::query()->create([
            'bom_id' => $this->bom->id,
            'work_center_id' => $this->workCenter->id,
            'sequence' => 1,
            'name' => 'Torneamento e Fresamento',
            'setup_time_minutes' => '0.00',
            'run_time_minutes_per_unit' => '30.00', // 0.5 hora * 60 R$/h = 30 R$
        ]);

        app(TenantContext::class)->clear();
    }

    public function test_partner_creation_and_listing_isolated_by_tenant(): void
    {
        // Tenant Alfa cria parceiro
        app(TenantContext::class)->set($this->tenantAlfa);
        Partner::query()->create([
            'partner_type' => PartnerType::Customer,
            'person_type' => PersonType::LegalEntity,
            'document_number' => '12.345.678/0001-99',
            'name' => 'Cliente Alfa',
        ]);
        app(TenantContext::class)->clear();

        // Tenant Beta cria parceiro
        app(TenantContext::class)->set($this->tenantBeta);
        Partner::query()->create([
            'partner_type' => PartnerType::Customer,
            'person_type' => PersonType::LegalEntity,
            'document_number' => '98.765.432/0001-11',
            'name' => 'Cliente Beta',
        ]);
        app(TenantContext::class)->clear();

        // Consulta autenticado como Alfa
        $response = $this->actingAs($this->userAlfa)
            ->withSession(['active_tenant_id' => $this->tenantAlfa->id])
            ->get('/app/crm/partners');

        $response->assertOk();
        $response->assertSee('Cliente Alfa');
        $response->assertDontSee('Cliente Beta');

        // Teste de RLS nativo no PostgreSQL
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("SELECT set_config('app.current_tenant_id', ?, false)", [$this->tenantAlfa->id]);
            $rawPartners = DB::table('partners')->pluck('name')->toArray();
            $this->assertContains('Cliente Alfa', $rawPartners);
            $this->assertNotContains('Cliente Beta', $rawPartners);
        }
    }

    public function test_crm_deal_creation_and_stage_drag_and_drop(): void
    {
        app(TenantContext::class)->set($this->tenantAlfa);

        $partner = Partner::query()->create([
            'partner_type' => PartnerType::Customer,
            'person_type' => PersonType::LegalEntity,
            'document_number' => '11.222.333/0001-44',
            'name' => 'Comprador Industrial Alfa',
        ]);

        $pipeline = CrmPipeline::query()->create([
            'name' => 'Funil Alfa',
            'is_default' => true,
        ]);

        $stage1 = CrmDealStage::query()->create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Prospecção',
            'sequence' => 1,
        ]);

        $stage2 = CrmDealStage::query()->create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Negociação',
            'sequence' => 2,
        ]);

        $deal = CrmDeal::query()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage1->id,
            'partner_id' => $partner->id,
            'title' => 'Lote de Válvulas Alta Pressão',
            'estimated_value' => '50000.00',
            'status' => DealStatus::Open,
            'priority' => DealPriority::High,
        ]);

        $this->assertEquals($stage1->id, $deal->stage_id);

        app(TenantContext::class)->clear();

        // Move etapa via PATCH
        $response = $this->actingAs($this->userAlfa)
            ->withSession(['active_tenant_id' => $this->tenantAlfa->id])
            ->patch("/app/crm/deals/{$deal->id}/stage", [
                'stage_id' => $stage2->id,
            ]);

        $response->assertRedirect();

        app(TenantContext::class)->set($this->tenantAlfa);
        $this->assertEquals($stage2->id, $deal->fresh()->stage_id);
        app(TenantContext::class)->clear();
    }

    public function test_quote_pricing_service_calculates_bom_materials_and_operations(): void
    {
        app(TenantContext::class)->set($this->tenantAlfa);

        $pricingService = app(QuotePricingService::class);

        // Material = 100.00, Operação = 30min * (60/h) = 30.00 => Custo Unit = 130.00
        // Markup 30% => Preço Unit = 130 * 1.30 = 169.00
        // Margem = (169 - 130) / 169 = 23.08%
        $result = $pricingService->calculateItemPricing([
            'item_id' => $this->manufacturedProduct->id,
            'bom_id' => $this->bom->id,
            'quantity' => '2.0000',
            'markup_percentage' => '30.00',
        ]);

        $this->assertEquals('100.0000', $result['materials_cost']);
        $this->assertEquals('30.0000', $result['operations_cost']);
        $this->assertEquals('130.0000', $result['unit_cost']);
        $this->assertEquals('169.0000', $result['unit_price']);
        $this->assertEquals('338.00', $result['total_price']);
        $this->assertEquals('23.08', $result['margin_percentage']);

        app(TenantContext::class)->clear();
    }

    public function test_atomic_one_click_quote_conversion_to_sales_order_and_production_order(): void
    {
        app(TenantContext::class)->set($this->tenantAlfa);

        $partner = Partner::query()->create([
            'partner_type' => PartnerType::Customer,
            'person_type' => PersonType::LegalEntity,
            'document_number' => '44.555.666/0001-77',
            'name' => 'Cliente Petroquímica S/A',
        ]);

        $pipeline = CrmPipeline::query()->create(['name' => 'Funil']);
        $stage = CrmDealStage::query()->create(['pipeline_id' => $pipeline->id, 'name' => 'Proposta', 'sequence' => 1]);

        $deal = CrmDeal::query()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'partner_id' => $partner->id,
            'title' => 'Fornecimento Válvulas Petroquímica',
            'estimated_value' => '1690.00',
            'status' => DealStatus::Open,
        ]);

        $quote = Quote::query()->create([
            'quote_number' => 'ORC-202610-0001',
            'partner_id' => $partner->id,
            'deal_id' => $deal->id,
            'status' => QuoteStatus::Approved,
            'total_materials_cost' => '1000.0000',
            'total_operations_cost' => '300.0000',
            'markup_percentage' => '30.00',
            'margin_percentage' => '23.08',
            'final_total' => '1690.00',
            'lead_time_days' => 10,
        ]);

        QuoteItem::query()->create([
            'quote_id' => $quote->id,
            'item_id' => $this->manufacturedProduct->id,
            'bom_id' => $this->bom->id,
            'quantity' => '10.0000',
            'unit_cost' => '130.0000',
            'markup_percentage' => '30.00',
            'unit_price' => '169.0000',
            'total_price' => '1690.00',
            'lead_time_days' => 10,
        ]);

        app(TenantContext::class)->clear();

        // Dispara conversão 1-clique via HTTP POST
        $response = $this->actingAs($this->userAlfa)
            ->withSession(['active_tenant_id' => $this->tenantAlfa->id])
            ->post("/app/sales/quotes/{$quote->id}/convert");

        $response->assertRedirect();

        // Reafirma contexto para inspecionar os modelos gerados
        app(TenantContext::class)->set($this->tenantAlfa);

        // 1. O Orçamento deve estar CONVERTED
        $quote->refresh();
        $this->assertEquals(QuoteStatus::Converted, $quote->status);
        $this->assertNotNull($quote->converted_at);

        // 2. Pedido de Venda criado
        /** @var SalesOrder $salesOrder */
        $salesOrder = SalesOrder::query()->where('quote_id', $quote->id)->first();
        $this->assertNotNull($salesOrder);
        $this->assertEquals(SalesOrderStatus::Confirmed, $salesOrder->status);
        $this->assertEquals('1690.00', $salesOrder->total_amount);

        // 3. Ordem de Produção disparada e vinculada no item do pedido
        $orderItem = $salesOrder->items()->first();
        $this->assertNotNull($orderItem);
        $this->assertNotNull($orderItem->production_order_id);

        $productionOrder = $orderItem->productionOrder;
        $this->assertNotNull($productionOrder);
        $this->assertEquals('10.0000', $productionOrder->planned_quantity);
        $this->assertEquals($this->bom->id, $productionOrder->bom_id);

        // 4. Oportunidade do CRM marcada como WON
        $deal->refresh();
        $this->assertEquals(DealStatus::Won, $deal->status);

        app(TenantContext::class)->clear();
    }

    public function test_cross_tenant_isolation_idor_and_foreign_key_tampering(): void
    {
        // Setup Tenant Beta
        app(TenantContext::class)->set($this->tenantBeta);

        $partnerBeta = Partner::query()->create([
            'partner_type' => PartnerType::Customer,
            'person_type' => PersonType::LegalEntity,
            'document_number' => '99.888.777/0001-66',
            'name' => 'Cliente Exclusivo Beta',
        ]);

        $quoteBeta = Quote::query()->create([
            'quote_number' => 'ORC-BETA-001',
            'partner_id' => $partnerBeta->id,
            'status' => QuoteStatus::Draft,
            'final_total' => '5000.00',
        ]);

        app(TenantContext::class)->clear();

        // 1. IDOR Read: Usuário Alfa tenta visualizar Orçamento do Beta -> 404 Not Found
        $responseRead = $this->actingAs($this->userAlfa)
            ->withSession(['active_tenant_id' => $this->tenantAlfa->id])
            ->get("/app/sales/quotes/{$quoteBeta->id}");

        $responseRead->assertNotFound();

        // 2. IDOR Convert: Usuário Alfa tenta converter Orçamento do Beta -> 404 Not Found
        $responseConvert = $this->actingAs($this->userAlfa)
            ->withSession(['active_tenant_id' => $this->tenantAlfa->id])
            ->post("/app/sales/quotes/{$quoteBeta->id}/convert");

        $responseConvert->assertNotFound();

        // 3. Foreign Key Cross-Tenant Injection: Usuário Alfa tenta criar Orçamento usando partner_id do Beta -> 422
        $responseInjection = $this->actingAs($this->userAlfa)
            ->withSession(['active_tenant_id' => $this->tenantAlfa->id])
            ->post('/app/sales/quotes', [
                'partner_id' => $partnerBeta->id, // Pertence a Beta!
                'markup_percentage' => '30.00',
                'items' => [
                    [
                        'item_id' => $this->manufacturedProduct->id,
                        'quantity' => '1.0000',
                    ],
                ],
            ]);

        $responseInjection->assertSessionHasErrors('partner_id');
    }
}
