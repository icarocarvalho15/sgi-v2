<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\CoreAdmin\Services\DefaultStatusesSeederService;
use App\Modules\CRM\Models\Partner;
use App\Modules\Finance\Models\BankAccount;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\CostCenter;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Models\FinancialPayment;
use App\Modules\Finance\Services\CashFlowReportService;
use App\Modules\Finance\Services\DreReportService;
use App\Modules\Finance\Services\FinancialEntryService;
use App\Modules\Finance\Services\FinancialPaymentService;
use App\Modules\Integrations\Fiscal\Actions\PrepareInvoiceAction;
use App\Modules\Integrations\Models\FiscalInvoice;
use App\Modules\Integrations\Models\PaymentGatewayConfig;
use App\Modules\Integrations\Models\TenantFiscalSetting;
use App\Modules\Integrations\Models\WebhookEvent;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteConversionService;
use App\Modules\SalesOrders\Models\SalesOrder;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class FullSystemDiagnosticTest extends TestCase
{
    private Tenant $tenantAlpha;
    private Tenant $tenantBeta;
    private User $userAlpha;
    private Partner $customerAlpha;
    private BankAccount $bankAlpha;
    private ChartOfAccount $revenueAccount;
    private CostCenter $costCenter;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar Tenant Alpha
        $this->tenantAlpha = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Alpha Diagnóstico',
            'subdomain' => 'alpha-diag-' . Str::random(6),
            'status' => 'active',
        ]);

        // Criar Tenant Beta
        $this->tenantBeta = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gráfica Beta Isolamento',
            'subdomain' => 'beta-diag-' . Str::random(6),
            'status' => 'active',
        ]);

        TenantContext::set($this->tenantAlpha);
        DefaultStatusesSeederService::seedForTenant($this->tenantAlpha->id);

        $this->userAlpha = User::create([
            'name' => 'Gestor Financeiro Alpha',
            'email' => 'fin@alpha-' . Str::random(5) . '.com',
            'password' => bcrypt('SenhaForte123!'),
            'tenant_id' => $this->tenantAlpha->id,
        ]);

        $this->customerAlpha = Partner::create([
            'name' => 'Empresa Cliente Alpha Ltda',
            'document_number' => '44.555.666/0001-77',
            'type' => 'customer',
        ]);

        $this->bankAlpha = BankAccount::create([
            'name' => 'Banco Itaú Empresas PJ',
            'current_balance' => '10000.0000',
            'is_default' => true,
        ]);

        $this->revenueAccount = ChartOfAccount::create([
            'code' => '1.01.001',
            'name' => 'Receitas Gráficas',
            'type' => 'revenue',
        ]);

        $this->costCenter = CostCenter::create([
            'code' => 'CC-OFFSET',
            'name' => 'Centro de Custo Offset',
        ]);
    }

    /**
     * Test 1: Lançamento de Títulos com Parcelamento e Precisão Zero Float
     */
    public function test_financial_entries_creation_with_installments_and_zero_float_precision(): void
    {
        $service = app(FinancialEntryService::class);

        // Criar título de R$ 100,00 parcelado em 3x
        // 100 / 3 = 33.3333; Parcela 1 = 33.3334, Parcela 2 = 33.3333, Parcela 3 = 33.3333
        $entries = $service->createEntriesWithInstallments([
            'type' => 'receivable',
            'partner_id' => $this->customerAlpha->id,
            'chart_of_account_id' => $this->revenueAccount->id,
            'cost_center_id' => $this->costCenter->id,
            'bank_account_id' => $this->bankAlpha->id,
            'description' => 'Serviços Gráficos 3x',
            'original_amount' => '100.0000',
            'first_due_date' => now()->addDays(30)->toDateString(),
            'total_installments' => 3,
        ]);

        $this->assertCount(3, $entries);
        $this->assertSame('33.3334', $entries[0]->original_amount);
        $this->assertSame('33.3333', $entries[1]->original_amount);
        $this->assertSame('33.3333', $entries[2]->original_amount);

        // A soma das 3 parcelas fecha exatamente em 100.0000 (Zero Float)
        $sum = bcadd(bcadd($entries[0]->original_amount, $entries[1]->original_amount, 4), $entries[2]->original_amount, 4);
        $this->assertSame('100.0000', $sum);
    }

    /**
     * Test 2: Baixa Parcial e Total de Título Financeiro com Atualização de Saldo Bancário
     */
    public function test_financial_payment_partial_and_full_liquidation_updates_bank_balance(): void
    {
        $entryService = app(FinancialEntryService::class);
        $paymentService = app(FinancialPaymentService::class);

        $entries = $entryService->createEntriesWithInstallments([
            'type' => 'receivable',
            'partner_id' => $this->customerAlpha->id,
            'description' => 'Venda de Banner',
            'original_amount' => '1000.0000',
            'first_due_date' => now()->addDays(15)->toDateString(),
            'total_installments' => 1,
        ]);

        $entry = $entries[0];
        $this->assertSame('1000.0000', $entry->balance_due);
        $this->assertSame('pending', $entry->status);

        // Baixa parcial 1: R$ 400,00 via Pix
        $payment1 = $paymentService->recordPayment($entry, [
            'amount' => '400.0000',
            'bank_account_id' => $this->bankAlpha->id,
            'payment_method' => 'pix',
            'notes' => 'Entrada de 40%',
        ]);

        $entry->refresh();
        $this->bankAlpha->refresh();

        $this->assertSame('partial', $entry->status);
        $this->assertSame('400.0000', $entry->paid_amount);
        $this->assertSame('600.0000', $entry->balance_due);
        $this->assertSame('10400.0000', $this->bankAlpha->current_balance);

        // Baixa final 2: R$ 600,00 quitando o título
        $payment2 = $paymentService->recordPayment($entry, [
            'amount' => '600.0000',
            'bank_account_id' => $this->bankAlpha->id,
            'payment_method' => 'pix',
            'notes' => 'Quitação final',
        ]);

        $entry->refresh();
        $this->bankAlpha->refresh();

        $this->assertSame('paid', $entry->status);
        $this->assertSame('1000.0000', $entry->paid_amount);
        $this->assertSame('0.0000', $entry->balance_due);
        $this->assertSame('11000.0000', $this->bankAlpha->current_balance);

        // Tentar baixar novamente deve falhar
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este título financeiro já foi liquidado integralmente.');
        $paymentService->recordPayment($entry, [
            'amount' => '10.0000',
            'bank_account_id' => $this->bankAlpha->id,
            'payment_method' => 'pix',
        ]);
    }

    /**
     * Test 3: Relatórios de Fluxo de Caixa (Realizado vs Projetado) e DRE Gerencial
     */
    public function test_cash_flow_and_dre_financial_reports_calculation(): void
    {
        $entryService = app(FinancialEntryService::class);
        $paymentService = app(FinancialPaymentService::class);

        // 1. Receita liquidada de R$ 5.000,00
        $recEntries = $entryService->createEntriesWithInstallments([
            'type' => 'receivable',
            'chart_of_account_id' => $this->revenueAccount->id,
            'description' => 'Serviços Gráficos Grandes Formatos',
            'original_amount' => '5000.0000',
            'first_due_date' => now()->toDateString(),
            'total_installments' => 1,
        ]);
        $paymentService->recordPayment($recEntries[0], [
            'amount' => '5000.0000',
            'bank_account_id' => $this->bankAlpha->id,
            'payment_method' => 'bank_transfer',
        ]);

        // 2. CPV (Custo de insumos) de R$ 2.000,00
        $cpvAccount = ChartOfAccount::create([
            'code' => '2.01.001',
            'name' => 'Custos de Papel',
            'type' => 'cost_of_goods',
        ]);
        $payEntries = $entryService->createEntriesWithInstallments([
            'type' => 'payable',
            'chart_of_account_id' => $cpvAccount->id,
            'description' => 'Compra de Papel Offset',
            'original_amount' => '2000.0000',
            'first_due_date' => now()->toDateString(),
            'total_installments' => 1,
        ]);
        $paymentService->recordPayment($payEntries[0], [
            'amount' => '2000.0000',
            'bank_account_id' => $this->bankAlpha->id,
            'payment_method' => 'bank_transfer',
        ]);

        // 3. Gerar Fluxo de Caixa
        $cashFlowService = app(CashFlowReportService::class);
        $cashFlow = $cashFlowService->generate(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());

        $this->assertSame('5000.0000', $cashFlow['realized_inflows']);
        $this->assertSame('2000.0000', $cashFlow['realized_outflows']);
        $this->assertSame('3000.0000', $cashFlow['realized_net']);

        // 4. Gerar DRE
        $dreService = app(DreReportService::class);
        $dre = $dreService->generate(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());

        $this->assertSame('5000.0000', $dre['gross_revenue']);
        $this->assertSame('2000.0000', $dre['cost_of_goods']);
        $this->assertSame('3000.0000', $dre['gross_profit']);
        $this->assertSame('3000.0000', $dre['net_profit']);
        $this->assertSame('60.00', $dre['margin_percentage']);
    }

    /**
     * Test 4: Armazenamento Criptografado no Cofre Vault (AES-256-GCM + HKDF)
     */
    public function test_payment_gateway_and_fiscal_settings_vault_encryption(): void
    {
        $gw = PaymentGatewayConfig::create([
            'gateway_name' => 'asaas',
            'is_active' => true,
            'mode' => 'sandbox',
        ]);

        $plainApiKey = 'ak_live_super_secret_key_12345';
        $plainWebhookSec = 'whsec_hmac_secret_token_98765';

        $gw->setApiKey($plainApiKey);
        $gw->setWebhookSecret($plainWebhookSec);
        $gw->save();

        // No banco de dados, o campo não contém o texto puro
        $rawRecord = PaymentGatewayConfig::withoutGlobalScopes()->where('id', $gw->id)->firstOrFail();
        $this->assertNotEquals($plainApiKey, $rawRecord->api_key_encrypted);
        $this->assertNotEquals($plainWebhookSec, $rawRecord->webhook_secret_encrypted);

        // Descriptografia em memória funciona perfeitamente
        $this->assertSame($plainApiKey, $gw->getDecryptedApiKey());
        $this->assertSame($plainWebhookSec, $gw->getDecryptedWebhookSecret());

        // Testar Certificado A1 no FiscalSettings
        $fiscal = TenantFiscalSetting::create([
            'cnpj' => '11.222.333/0001-99',
            'tax_regime' => 'simples_nacional',
        ]);

        $fakePfxContent = 'BINARY_PKCS12_CERTIFICATE_CONTENT_TEST';
        $fakePassword = 'senha_forte_certificado';

        $fiscal->setCertificateA1($fakePfxContent, $fakePassword);
        $fiscal->save();

        $this->assertSame($fakePfxContent, $fiscal->getDecryptedCertificateA1());
        $this->assertSame($fakePassword, $fiscal->getDecryptedCertificatePassword());
    }

    /**
     * Test 5: Receptor de Webhook com Validação HMAC e Idempotência Estrita
     */
    public function test_webhook_idempotency_and_hmac_signature_verification(): void
    {
        $entryService = app(FinancialEntryService::class);
        $entries = $entryService->createEntriesWithInstallments([
            'type' => 'receivable',
            'partner_id' => $this->customerAlpha->id,
            'description' => 'Cobrança via Link Asaas',
            'original_amount' => '750.0000',
            'first_due_date' => now()->addDays(10)->toDateString(),
            'total_installments' => 1,
        ]);
        $entry = $entries[0];

        $gw = PaymentGatewayConfig::create([
            'gateway_name' => 'asaas',
            'is_active' => true,
        ]);
        $gw->setWebhookSecret('my_secure_asaas_webhook_token');
        $gw->save();

        $eventId = 'evt_test_' . Str::random(10);
        $chargeId = 'pay_asaas_123456';
        $entry->update(['entry_number' => $chargeId]);

        $payload = [
            'id' => $eventId,
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => $chargeId,
                'value' => 750.00,
                'paymentDate' => now()->toDateString(),
            ],
        ];

        // 1. Envio sem token ou com token inválido -> Deve retornar 401
        $invalidResponse = $this->postJson("/api/webhooks/asaas?tenant_id={$this->tenantAlpha->id}", $payload, [
            'asaas-access-token' => 'token_falso',
        ]);
        $invalidResponse->assertStatus(401);

        // 2. Envio com token HMAC correto -> Deve retornar 200 e baixar o título
        $validResponse = $this->postJson("/api/webhooks/asaas?tenant_id={$this->tenantAlpha->id}", $payload, [
            'asaas-access-token' => 'my_secure_asaas_webhook_token',
        ]);
        $validResponse->assertStatus(200);

        // Restaurar contexto local após o middleware
        TenantContext::set($this->tenantAlpha);
        $entry->refresh();
        $this->assertSame('paid', $entry->status);
        $this->assertSame('750.0000', $entry->paid_amount);

        // Verificar que o evento foi gravado na tabela webhook_events
        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'asaas',
            'external_event_id' => $eventId,
            'status' => 'processed',
        ]);

        // 3. Teste de Idempotência: reenvio do mesmo evento não deve duplicar o pagamento
        $duplicateResponse = $this->postJson("/api/webhooks/asaas?tenant_id={$this->tenantAlpha->id}", $payload, [
            'asaas-access-token' => 'my_secure_asaas_webhook_token',
        ]);
        $duplicateResponse->assertStatus(200);
        $this->assertSame('Evento já processado anteriormente (idempotente).', $duplicateResponse->json('message'));
    }

    /**
     * Test 6: Emissão Fiscal Assíncrona NFe via TenantAwareJob
     */
    public function test_asynchronous_fiscal_invoice_emission(): void
    {
        $fiscalSettings = TenantFiscalSetting::create([
            'cnpj' => '11.222.333/0001-99',
            'state_registration' => '123.456.789',
            'tax_regime' => 'simples_nacional',
            'environment' => 'sandbox',
            'next_nfe_number' => 500,
            'nfe_series' => 1,
            'default_ncm' => '49111090',
            'default_cfop' => '5101',
        ]);
        $fiscalSettings->setCertificateA1('CERT_MOCK', '1234');
        $fiscalSettings->save();

        $quoteStatus = TenantStatus::forModule('quotes')->firstOrFail();
        $quote = Quote::create([
            'customer_id' => $this->customerAlpha->id,
            'status_id' => $quoteStatus->id,
            'title' => 'Flyers Promocionais',
            'quote_number' => 'ORC-FLYER-001',
            'valid_until' => now()->addDays(10)->toDateString(),
            'total_amount' => '1200.0000',
        ]);

        $quote->items()->create([
            'product_type' => 'commercial_print',
            'name' => 'Flyer 10x15cm 4x4 Cores',
            'quantity' => 5000,
            'unit_price' => '0.2400',
            'total_price' => '1200.0000',
        ]);

        $conversion = QuoteConversionService::convert($quote, 'PV-FLYER-001');
        /** @var SalesOrder $salesOrder */
        $salesOrder = $conversion['sales_order'];

        $prepareAction = app(PrepareInvoiceAction::class);
        $invoice = $prepareAction->execute($salesOrder, $this->customerAlpha, 'nfe');

        // Restaurar o contexto do tenant no processo do teste após o job em sync ter limpado na trait
        TenantContext::set($this->tenantAlpha);

        // Como a fila roda em 'sync', o IssueFiscalInvoiceJob já concluiu a emissão!
        $invoice->refresh();

        $this->assertSame('authorized', $invoice->status);
        $this->assertSame(500, $invoice->invoice_number);
        $this->assertNotEmpty($invoice->access_key);
        $this->assertEquals(44, strlen($invoice->access_key)); // Chave SEFAZ válida com 44 dígitos
        $this->assertNotEmpty($invoice->protocol_number);
        $this->assertNotNull($invoice->issued_at);
    }

    /**
     * Test 7: Isolamento Rigoroso Cross-Tenant no Financeiro e Fiscais (RLS Fail-Closed)
     */
    public function test_strict_cross_tenant_isolation_in_finance_and_gateways(): void
    {
        $entryService = app(FinancialEntryService::class);
        $entryService->createEntriesWithInstallments([
            'type' => 'receivable',
            'partner_id' => $this->customerAlpha->id,
            'description' => 'Recebimento Confidencial Alpha',
            'original_amount' => '50000.0000',
            'first_due_date' => now()->addDays(30)->toDateString(),
            'total_installments' => 1,
        ]);

        $this->assertCount(1, FinancialEntry::all());
        $this->assertCount(1, BankAccount::all());

        // Alterna para o contexto do Tenant Beta
        TenantContext::set($this->tenantBeta);

        // O Tenant Beta não deve ver nenhum registro financeiro do Tenant Alpha
        $this->assertCount(0, FinancialEntry::all());
        $this->assertCount(0, FinancialPayment::all());
        $this->assertCount(0, BankAccount::all());
        $this->assertCount(0, ChartOfAccount::all());
        $this->assertCount(0, CostCenter::all());
        $this->assertCount(0, TenantFiscalSetting::all());
        $this->assertCount(0, FiscalInvoice::all());
    }
}
