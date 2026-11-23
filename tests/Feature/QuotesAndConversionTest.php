<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\CoreAdmin\Services\DefaultStatusesSeederService;
use App\Modules\CRM\Models\Partner;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use App\Modules\Quotes\Services\GraphicCostCalculatorService;
use App\Modules\Quotes\Services\QuoteConversionService;
use App\Modules\SalesOrders\Models\SalesOrder;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuotesAndConversionTest extends TestCase
{
    private Tenant $tenant;
    private Partner $customer;
    private TenantStatus $quoteDraftStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Modelo Offset & Digital',
            'subdomain' => 'grafica-' . Str::random(6),
            'status' => 'active',
        ]);

        TenantContext::set($this->tenant);

        // Seed default statuses for this tenant
        DefaultStatusesSeederService::seedForTenant($this->tenant->id);

        $this->quoteDraftStatus = TenantStatus::forModule('quotes')
            ->where('slug', 'rascunho-tecnico')
            ->firstOrFail();

        $this->customer = Partner::create([
            'type' => 'customer',
            'name' => 'Empresa Cliente Teste S/A',
            'document_number' => '11222333000181',
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_graphic_cost_calculator_zero_float_accuracy(): void
    {
        // 1.000 unidades, 4x4 cores (8 chapas CTP)
        $params = [
            'quantity' => 1000,
            'colors_front' => 4,
            'colors_back' => 4,
            'ctp_plate_cost' => '35.0000',               // 8 * 35 = 280.0000
            'substrate_unit_cost' => '0.5000',          // 1000 * 0.50 = 500.0000
            'machine_run_cost_per_thousand' => '40.0000',// 1 milheiro * 40 = 40.0000
            'finishing_flat_cost' => '80.0000',          // 80.0000
            'markup_multiplier' => '1.5000',            // Custo total * 1.5
        ];

        $calc = GraphicCostCalculatorService::calculate($params);

        // Total Cost = 280 + 500 + 40 + 80 = 900.0000
        $this->assertEquals(8, $calc['ctp_plates_count']);
        $this->assertEquals('280.0000', $calc['ctp_cost']);
        $this->assertEquals('500.0000', $calc['substrate_cost']);
        $this->assertEquals('40.0000', $calc['machine_run_cost']);
        $this->assertEquals('80.0000', $calc['finishing_cost']);
        $this->assertEquals('900.0000', $calc['total_estimated_cost']);

        // Unit Cost = 900 / 1000 = 0.9000
        $this->assertEquals('0.9000', $calc['unit_cost']);

        // Unit Price = 0.9000 * 1.5 = 1.3500
        $this->assertEquals('1.3500', $calc['unit_price']);

        // Total Price = 1.3500 * 1000 = 1350.0000
        $this->assertEquals('1350.0000', $calc['total_price']);

        // Gross Margin = 1350 - 900 = 450.0000
        $this->assertEquals('450.0000', $calc['margin_amount']);
    }

    public function test_it_creates_quote_with_items_and_converts_in_one_click(): void
    {
        $quote = Quote::create([
            'quote_number' => 'ORC-2026-TEST01',
            'customer_id' => $this->customer->id,
            'status_id' => $this->quoteDraftStatus->id,
            'title' => 'Folder 4x4 Couché 150g',
            'subtotal' => '1350.0000',
            'total_amount' => '1350.0000',
            'estimated_cost' => '900.0000',
            'production_lead_time_days' => 5,
        ]);

        $item = $quote->items()->create([
            'product_type' => 'commercial_print',
            'name' => 'Folder Institucional A4',
            'quantity' => 1000,
            'format_open' => '297x420 mm',
            'format_closed' => '210x297 mm',
            'substrate_type' => 'Papel Couché Fosco 150g',
            'colors_front' => 4,
            'colors_back' => 4,
            'ctp_plates_count' => 8,
            'unit_cost' => '0.9000',
            'markup_multiplier' => '1.5000',
            'unit_price' => '1.3500',
            'total_price' => '1350.0000',
            'finishing_operations' => ['Refile Guilhotina', 'Dobra Cruzada'],
        ]);

        $this->assertNotEmpty($quote->id);
        $this->assertCount(1, $quote->items);

        // Dispara a Conversão em 1-Clique
        $result = QuoteConversionService::convert($quote, 'PV-2026-0001');

        // 1. Verifica geração do Pedido de Venda
        $salesOrder = $result['sales_order'];
        $this->assertInstanceOf(SalesOrder::class, $salesOrder);
        $this->assertEquals('PV-2026-0001', $salesOrder->order_number);
        $this->assertEquals($this->customer->id, $salesOrder->customer_id);
        $this->assertEquals('1350.0000', $salesOrder->total_amount);
        $this->assertEquals($this->tenant->id, $salesOrder->tenant_id);

        // 2. Verifica geração da Ordem de Produção (OP)
        $this->assertCount(1, $result['production_orders']);
        $prodOrder = $result['production_orders'][0];
        $this->assertInstanceOf(ProductionOrder::class, $prodOrder);
        $this->assertEquals('Folder Institucional A4', $prodOrder->product_name);
        $this->assertEquals(1000, $prodOrder->target_quantity);
        $this->assertEquals($salesOrder->id, $prodOrder->sales_order_id);
        $this->assertEquals($this->tenant->id, $prodOrder->tenant_id);

        // Status inicial de produção: pre-impressao
        $this->assertEquals('pre-impressao', $prodOrder->status->slug);

        // 3. Verifica atualização do status do orçamento para aprovado e metadata
        $quote->refresh();
        $this->assertEquals('orcamento-aprovado', $quote->status->slug);
        $this->assertEquals($salesOrder->id, $quote->metadata['converted_to_order_id']);
    }

    public function test_cross_tenant_isolation_on_quotes_and_production_orders(): void
    {
        $tenantB = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Concorrente Beta',
            'subdomain' => 'beta-' . Str::random(6),
            'status' => 'active',
        ]);
        DefaultStatusesSeederService::seedForTenant($tenantB->id);

        // Criar orçamento no Tenant A
        TenantContext::set($this->tenant);
        $quoteA = Quote::create([
            'quote_number' => 'ORC-ALPHA-01',
            'customer_id' => $this->customer->id,
            'status_id' => $this->quoteDraftStatus->id,
            'title' => 'Orçamento Confidencial Alpha',
            'total_amount' => '5000.0000',
        ]);

        // Mudar contexto para Tenant B
        TenantContext::set($tenantB);
        $quotesInB = Quote::all();
        $this->assertFalse($quotesInB->contains('id', $quoteA->id));

        // Tentar buscar diretamente por ID
        $found = Quote::find($quoteA->id);
        $this->assertNull($found);
    }

    private function createTenantUser(): User
    {
        return User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Operador Comercial',
            'email' => 'operador-' . Str::random(6) . '@grafica.com',
            'password' => 'secret123',
            'is_super_admin' => false,
        ]);
    }

    public function test_quote_index_provides_kanban_and_statuses(): void
    {
        $user = $this->createTenantUser();

        Quote::create([
            'quote_number' => 'ORC-TEST-KANBAN',
            'customer_id' => $this->customer->id,
            'status_id' => $this->quoteDraftStatus->id,
            'title' => 'Flyer Promocional 5000 un',
            'subtotal' => '850.0000',
            'total_amount' => '850.0000',
            'estimated_cost' => '400.0000',
            'production_lead_time_days' => 3,
        ]);

        $response = $this->actingAs($user)
            ->get(route('quotes.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Quotes/Index')
            ->has('quotes')
            ->has('kanbanQuotes', 1)
            ->has('statuses')
        );
    }

    public function test_quote_status_can_be_updated_in_funnel(): void
    {
        $user = $this->createTenantUser();

        $quote = Quote::create([
            'quote_number' => 'ORC-TEST-STATUS',
            'customer_id' => $this->customer->id,
            'status_id' => $this->quoteDraftStatus->id,
            'title' => 'Sacolas Personalizadas Kraft',
            'subtotal' => '2400.0000',
            'total_amount' => '2400.0000',
            'estimated_cost' => '1200.0000',
            'production_lead_time_days' => 7,
        ]);

        $approvedStatus = TenantStatus::forModule('quotes')
            ->where('slug', 'orcamento-aprovado')
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->post(route('quotes.status', $quote), [
                'status_id' => $approvedStatus->id,
            ]);

        $response->assertRedirect();
        TenantContext::set($this->tenant);
        $quote->refresh();
        $this->assertEquals($approvedStatus->id, $quote->status_id);
    }

    public function test_legacy_crm_kanban_route_redirects_to_quotes_kanban(): void
    {
        $user = $this->createTenantUser();

        $response = $this->actingAs($user)
            ->get(route('crm.kanban.index'));

        $response->assertRedirect(route('quotes.index', ['view' => 'kanban']));
    }
}
