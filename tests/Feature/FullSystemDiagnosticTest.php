<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\CRM\Actions\ConvertQuoteToSalesOrderAction;
use App\Modules\CRM\Enums\DealPriority;
use App\Modules\CRM\Enums\DealStatus;
use App\Modules\CRM\Enums\QuoteStatus;
use App\Modules\CRM\Enums\SalesOrderStatus;
use App\Modules\CRM\Models\CrmDeal;
use App\Modules\CRM\Models\CrmDealStage;
use App\Modules\CRM\Models\CrmPipeline;
use App\Modules\CRM\Models\Partner;
use App\Modules\CRM\Models\Quote;
use App\Modules\CRM\Models\QuoteItem;
use App\Modules\CRM\Models\SalesOrder;
use App\Modules\CRM\Models\SalesOrderItem;
use App\Modules\Finance\DTOs\CreateFinancialEntryDTO;
use App\Modules\Finance\Enums\EntryStatus;
use App\Modules\Finance\Enums\EntryType;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\CostCenter;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Models\FinancialPayment;
use App\Modules\Finance\Services\FinancialEntryService;
use App\Modules\Fiscal\Enums\FiscalEnvironment;
use App\Modules\Fiscal\Enums\FiscalInvoiceStatus;
use App\Modules\Fiscal\Enums\FiscalInvoiceType;
use App\Modules\Fiscal\Enums\PaymentGateway;
use App\Modules\Fiscal\Models\FiscalInvoice;
use App\Modules\Fiscal\Models\PaymentGatewaySetting;
use App\Modules\Fiscal\Models\TenantTaxSetting;
use App\Modules\Fiscal\Models\WebhookEvent;
use App\Modules\Fiscal\Services\FiscalEmitterService;
use App\Modules\Fiscal\Services\WebhookService;
use App\Modules\Inventory\Enums\KardexMovementType;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\KardexMovement;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Production\DTOs\RecordProductionDTO;
use App\Modules\Production\Enums\ProductionOrderStatus;
use App\Modules\Production\Models\Bom;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Production\Models\ProductionOrderMaterial;
use App\Modules\Production\Models\ProductionOrderOperation;
use App\Modules\Production\Models\WorkCenter;
use App\Modules\Production\Services\ProductionOrderService;
use Carbon\Carbon;
use Database\Seeders\FullSystemDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class FullSystemDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantAlfa;

    private Tenant $tenantBeta;

    private User $userAlfa;

    private User $userBeta;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(FullSystemDemoSeeder::class);

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

        $this->setTenantContext($this->tenantAlfa);
    }

    private function setTenantContext(Tenant $tenant): void
    {
        app(TenantContext::class)->set($tenant);
        setPermissionsTeamId($tenant->id);

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("SELECT set_config('app.current_tenant_id', ?, false)", [$tenant->id]);
        }
    }

    /**
     * Teste de Fumaça Integrado: Executa o ciclo de vida completo de ponta a ponta
     * CRM Quote -> Pedido de Venda -> Ordem de Produção (com explosão de BOM e Roteiro)
     * -> Apontamento de Produção e Baixa Atômica no Kardex
     * -> Geração de Contas a Receber
     * -> Liquidação Automática via Webhook com Idempotência
     * -> Emissão e Autorização de NF-e com XML e Chave de 44 dígitos
     */
    public function test_full_integrated_lifecycle_from_quote_to_cash_and_fiscal(): void
    {
        $this->setTenantContext($this->tenantAlfa);

        // 1. Recupera entidades essenciais semeadas no Tenant Alfa
        $customer = Partner::where('document_number', '12.888.999/0001-44')->firstOrFail();
        $itemFinished = Item::where('sku', 'PA-RED-50')->firstOrFail();
        $itemBar1020 = Item::where('sku', 'MP-BAR-1020')->firstOrFail();
        $bom = Bom::where('code', 'BOM-RED-50-V1')->firstOrFail();
        $pipeline = CrmPipeline::where('name', 'Vendas Industriais Sob Encomenda')->firstOrFail();
        $stageProposal = CrmDealStage::where('name', 'Orçamento Técnico')->firstOrFail();
        $warehouseRaw = Warehouse::where('code', 'ALM-01')->firstOrFail();
        $warehouseFinished = Warehouse::where('code', 'ALM-PA')->firstOrFail();

        // Saldo inicial de MP antes do novo ciclo
        $initialBalanceBar = InventoryBalance::where('warehouse_id', $warehouseRaw->id)
            ->where('item_id', $itemBar1020->id)
            ->value('quantity_on_hand') ?? '0.0000';

        // 2. Cria nova Oportunidade no CRM
        $deal = CrmDeal::create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageProposal->id,
            'partner_id' => $customer->id,
            'title' => 'Fornecimento Adicional de 2 Redutores RV-50',
            'estimated_value' => '1033.20',
            'expected_close_date' => Carbon::now()->addDays(10)->toDateString(),
            'status' => DealStatus::Open,
            'priority' => DealPriority::High,
            'assigned_user_id' => $this->userAlfa->id,
        ]);

        // 3. Elabora Orçamento Técnico com Ficha Técnica vinculada
        $quote = Quote::create([
            'quote_number' => 'ORC-DIAG-2026',
            'partner_id' => $customer->id,
            'deal_id' => $deal->id,
            'status' => QuoteStatus::Approved,
            'total_materials_cost' => '438.0000', // 2 un * 219.00
            'total_operations_cost' => '300.0000', // 2 un * 150.00
            'markup_percentage' => '40.00',
            'margin_percentage' => '28.57',
            'final_total' => '1033.20',
            'lead_time_days' => 10,
            'valid_until' => Carbon::now()->addDays(20),
            'approved_at' => Carbon::now(),
        ]);

        QuoteItem::create([
            'quote_id' => $quote->id,
            'item_id' => $itemFinished->id,
            'bom_id' => $bom->id,
            'quantity' => '2.0000',
            'unit_cost' => '369.0000',
            'markup_percentage' => '40.00',
            'unit_price' => '516.6000',
            'total_price' => '1033.20',
            'lead_time_days' => 10,
        ]);

        // 4. Executa Conversão em 1-Clique em Pedido de Venda e Ordem de Produção
        /** @var ConvertQuoteToSalesOrderAction $convertAction */
        $convertAction = app(ConvertQuoteToSalesOrderAction::class);
        $salesOrder = $convertAction->execute($quote);

        // Asserções do CRM e Vendas
        $this->assertEquals(QuoteStatus::Converted, $quote->fresh()->status);
        $this->assertNotNull($quote->fresh()->converted_at);
        $this->assertEquals(DealStatus::Won, $deal->fresh()->status);
        $this->assertEquals(SalesOrderStatus::Confirmed, $salesOrder->status);
        $this->assertEquals('1033.20', (string) $salesOrder->total_amount);

        // 5. Validação da Geração e Liberação Automática da OP com explosão de BOM
        /** @var SalesOrderItem $orderItem */
        $orderItem = $salesOrder->items()->firstOrFail();
        $this->assertNotNull($orderItem->production_order_id);

        /** @var ProductionOrder $productionOrder */
        $productionOrder = ProductionOrder::findOrFail($orderItem->production_order_id);
        $this->assertEquals(ProductionOrderStatus::Released, $productionOrder->status);
        $this->assertEquals('2.0000', (string) $productionOrder->planned_quantity);
        $this->assertEquals('0.0000', (string) $productionOrder->produced_quantity);

        // Confirma que os materiais e operações foram explodidos no empenho
        $materials = ProductionOrderMaterial::where('production_order_id', $productionOrder->id)->get();
        $operations = ProductionOrderOperation::where('production_order_id', $productionOrder->id)->get();
        $this->assertCount(3, $materials);
        $this->assertCount(3, $operations);

        // 6. Apontamento Real de Produção (2 unidades concluídas)
        /** @var ProductionOrderService $poService */
        $poService = app(ProductionOrderService::class);
        $poService->recordProduction(new RecordProductionDTO(
            productionOrderId: $productionOrder->id,
            producedQuantity: '2.0000',
            scrappedQuantity: '0.0000',
            notes: 'Diagnóstico: Lote integral de 2 unidades concluído.',
            userId: $this->userAlfa->id,
        ));

        // Validação no Módulo de Produção
        $freshPo = $productionOrder->fresh();
        $this->assertEquals('2.0000', (string) $freshPo->produced_quantity);
        $this->assertEquals(ProductionOrderStatus::Completed, $freshPo->status);

        // 7. Validação do Kardex (Razão Imutável e Baixa Atômica)
        // 2 unidades consomem 25.2000 KG de Aço 1020 (12 KG x 2 un + 5% perda/scrap rate cadastrado na BOM)
        $outMovement = KardexMovement::where('reference_type', 'production_orders')
            ->where('reference_id', $productionOrder->id)
            ->where('movement_type', KardexMovementType::OutProduction)
            ->where('item_id', $itemBar1020->id)
            ->firstOrFail();

        $this->assertEquals('25.2000', (string) $outMovement->quantity);

        $expectedBarBalance = bcsub($initialBalanceBar, '25.2000', 4);
        $actualBarBalance = InventoryBalance::where('warehouse_id', $warehouseRaw->id)
            ->where('item_id', $itemBar1020->id)
            ->value('quantity_on_hand');
        $this->assertEquals($expectedBarBalance, (string) $actualBarBalance);

        // Confirma a entrada do Produto Acabado no Almoxarifado PA
        $inMovementFinished = KardexMovement::where('reference_type', 'production_orders')
            ->where('reference_id', $productionOrder->id)
            ->where('movement_type', KardexMovementType::InProduction)
            ->where('item_id', $itemFinished->id)
            ->firstOrFail();
        $this->assertEquals('2.0000', (string) $inMovementFinished->quantity);

        // 8. Faturamento Financeiro: Lançamento em Contas a Receber
        /** @var ChartOfAccount $accountRevenue */
        $accountRevenue = ChartOfAccount::where('code', '3.1.01')->firstOrFail();
        /** @var FinancialAccount $bankItau */
        $bankItau = FinancialAccount::where('name', 'Banco Itaú Empresas C/C')->firstOrFail();
        $initialBankBalance = (string) $bankItau->current_balance;

        /** @var FinancialEntryService $finEntryService */
        $finEntryService = app(FinancialEntryService::class);
        $entries = $finEntryService->createWithInstallments(new CreateFinancialEntryDTO(
            entryType: EntryType::Receivable,
            chartOfAccountId: $accountRevenue->id,
            documentNumber: $salesOrder->order_number,
            title: "Faturamento Pedido {$salesOrder->order_number}",
            totalAmount: (string) $salesOrder->total_amount,
            firstDueDate: Carbon::now()->toDateString(),
            partnerId: $customer->id,
            salesOrderId: $salesOrder->id,
            installmentsCount: 1,
            issueDate: Carbon::now()->toDateString(),
        ));

        /** @var FinancialEntry $receivableEntry */
        $receivableEntry = $entries->firstOrFail();
        $this->assertEquals(EntryStatus::Pending, $receivableEntry->status);
        $this->assertEquals('1033.20', (string) $receivableEntry->balance_amount);

        // 9. Simulação de Recebimento via Webhook do Gateway Asaas
        /** @var WebhookService $webhookService */
        $webhookService = app(WebhookService::class);

        $mockPayload = [
            'id' => 'evt_diag_test_9988',
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_diag_test_01',
                'value' => '1033.20',
                'netValue' => '1033.20',
                'externalReference' => $receivableEntry->id,
                'billingType' => 'PIX',
                'clientPaymentDate' => Carbon::now()->toDateString(),
            ],
        ];

        $rawJson = (string) json_encode($mockPayload);

        // Executa Webhook com assinatura válida do gateway
        $gatewaySetting = PaymentGatewaySetting::where('gateway', PaymentGateway::Asaas)->firstOrFail();
        $signature = (string) $gatewaySetting->getDecryptedWebhookSecret();

        $webhookResult = $webhookService->handle(
            gateway: PaymentGateway::Asaas,
            rawPayload: $rawJson,
            signatureHeader: $signature,
            payload: $mockPayload
        );

        $this->assertEquals('processed', $webhookResult['status']);
        $this->assertTrue($webhookResult['settled']);

        // Validação da Liquidação no Contas a Receber e Saldo Bancário
        $freshEntry = $receivableEntry->fresh();
        $this->assertEquals(EntryStatus::Paid, $freshEntry->status);
        $this->assertEquals('0.00', (string) $freshEntry->balance_amount);
        $this->assertEquals('1033.20', (string) $freshEntry->paid_amount);

        $freshBank = $bankItau->fresh();
        $expectedBankBalance = bcadd($initialBankBalance, '1033.20', 2);
        $this->assertEquals($expectedBankBalance, (string) $freshBank->current_balance);

        // Validação da Idempotência: Segundo disparo do mesmo webhook não duplica saldo nem pagamento
        $idempotentResult = $webhookService->handle(
            gateway: PaymentGateway::Asaas,
            rawPayload: $rawJson,
            signatureHeader: $signature,
            payload: $mockPayload
        );

        $this->assertEquals('already_processed', $idempotentResult['status']);
        $this->assertFalse($idempotentResult['settled']);
        $this->assertEquals($expectedBankBalance, (string) $freshBank->fresh()->current_balance);

        // 10. Emissão Fiscal Assíncrona (NF-e de Mercadorias)
        $invoice = FiscalInvoice::create([
            'sales_order_id' => $salesOrder->id,
            'partner_id' => $customer->id,
            'invoice_type' => FiscalInvoiceType::Nfe,
            'invoice_number' => 102,
            'series' => 1,
            'environment' => FiscalEnvironment::Homologation,
            'status' => FiscalInvoiceStatus::Draft,
            'total_amount' => (string) $salesOrder->total_amount,
            'tax_amount' => '62.00',
            'issued_at' => Carbon::now(),
        ]);

        /** @var FiscalEmitterService $fiscalService */
        $fiscalService = app(FiscalEmitterService::class);
        $authorizedInvoice = $fiscalService->emitNfe($invoice);

        $this->assertEquals(FiscalInvoiceStatus::Authorized, $authorizedInvoice->status);
        $this->assertEquals(44, strlen((string) $authorizedInvoice->access_key));
        $this->assertNotNull($authorizedInvoice->protocol_number);
        $this->assertNotNull($authorizedInvoice->xml_path);
        Storage::disk('local')->assertExists((string) $authorizedInvoice->xml_path);
    }

    /**
     * Validação Estrita de Isolamento Multi-Tenant em todos os 6 módulos
     * Assegura vazamento zero de dados de Alfa para Beta
     */
    public function test_strict_multi_tenant_isolation_across_all_six_modules(): void
    {
        // 1. Sob o contexto do Tenant Alfa, valida que os dados existem
        $this->setTenantContext($this->tenantAlfa);

        $this->assertTrue(Partner::where('document_number', '12.888.999/0001-44')->exists());
        $this->assertTrue(CrmDeal::where('title', 'like', '%Redutores%')->exists());
        $this->assertTrue(Quote::where('quote_number', 'ORC-2026-001')->exists());
        $this->assertTrue(Item::where('sku', 'PA-RED-50')->exists());
        $this->assertTrue(Bom::where('code', 'BOM-RED-50-V1')->exists());
        $this->assertTrue(WorkCenter::where('code', 'WC-CNC-01')->exists());
        $this->assertTrue(FinancialAccount::where('name', 'Banco Itaú Empresas C/C')->exists());
        $this->assertTrue(ChartOfAccount::where('code', '3.1.01')->exists());
        $this->assertTrue(CostCenter::where('code', 'CC-USIN')->exists());
        $this->assertTrue(TenantTaxSetting::where('nfe_series', 1)->exists());
        $this->assertTrue(PaymentGatewaySetting::where('gateway', PaymentGateway::Asaas)->exists());
        $this->assertTrue(FiscalInvoice::where('invoice_number', 100)->exists());

        // 2. Comuta para o contexto do Tenant Beta (Indústria Beta S/A)
        $this->setTenantContext($this->tenantBeta);

        // Módulo 1 & 4: CRM e Vendas
        $this->assertFalse(Partner::where('document_number', '12.888.999/0001-44')->exists(), 'Vazamento em CRM Partners');
        $this->assertFalse(CrmDeal::where('title', 'like', '%Redutores%')->exists(), 'Vazamento em CRM Deals');
        $this->assertFalse(Quote::where('quote_number', 'ORC-2026-001')->exists(), 'Vazamento em Quotes');
        $this->assertFalse(SalesOrder::where('notes', 'like', '%Alfa%')->exists(), 'Vazamento em Sales Orders');

        // Módulo 2: Suprimentos & Kardex
        $this->assertFalse(Item::where('sku', 'PA-RED-50')->exists(), 'Vazamento em Itens PA');
        $this->assertFalse(Item::where('sku', 'MP-BAR-1020')->exists(), 'Vazamento em Itens MP');
        $this->assertFalse(Warehouse::where('code', 'ALM-PA')->exists(), 'Vazamento em Depósitos');
        $this->assertEquals(0, KardexMovement::count(), 'Vazamento em Kardex Movements');
        $this->assertEquals(0, InventoryBalance::count(), 'Vazamento em Inventory Balances');

        // Módulo 3: Engenharia & Produção
        $this->assertFalse(Bom::where('code', 'BOM-RED-50-V1')->exists(), 'Vazamento em Fichas Técnicas (BOM)');
        $this->assertFalse(WorkCenter::where('code', 'WC-CNC-01')->exists(), 'Vazamento em Centros de Trabalho');
        $this->assertEquals(0, ProductionOrder::count(), 'Vazamento em Ordens de Produção');

        // Módulo 5: Financeiro
        $this->assertFalse(FinancialAccount::where('name', 'Banco Itaú Empresas C/C')->exists(), 'Vazamento em Contas Bancárias');
        $this->assertFalse(ChartOfAccount::where('code', '3.1.01')->exists(), 'Vazamento em Plano de Contas');
        $this->assertFalse(CostCenter::where('code', 'CC-USIN')->exists(), 'Vazamento em Centros de Custo');
        $this->assertEquals(0, FinancialEntry::count(), 'Vazamento em Títulos Financeiros');
        $this->assertEquals(0, FinancialPayment::count(), 'Vazamento em Pagamentos');

        // Módulo 6: Fiscal & Gateways
        $this->assertFalse(TenantTaxSetting::exists(), 'Vazamento em Configurações Fiscais');
        $this->assertFalse(PaymentGatewaySetting::exists(), 'Vazamento em Gateways de Pagamento');
        $this->assertFalse(FiscalInvoice::exists(), 'Vazamento em Notas Fiscais');
        $this->assertEquals(0, WebhookEvent::count(), 'Vazamento em Eventos de Webhook');

        // Confirma que o Tenant Beta possui apenas os seus próprios dados isolados
        $this->assertTrue(Partner::where('document_number', '99.888.777/0001-55')->exists());
        $this->assertTrue(Item::where('sku', 'MP-BETA-ALUM')->exists());
        $this->assertTrue(FinancialAccount::where('name', 'Banco Santander Beta C/C')->exists());
        $this->assertTrue(Warehouse::where('code', 'ALM-BETA-01')->exists());
    }
}
