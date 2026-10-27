<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Security\VaultEncryption;
use App\Core\Tenancy\Exceptions\CrossTenantTamperingException;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\CRM\Enums\PartnerType;
use App\Modules\CRM\Enums\PersonType;
use App\Modules\CRM\Models\Partner;
use App\Modules\Finance\Enums\AccountCategoryType;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\EntryStatus;
use App\Modules\Finance\Enums\EntryType;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Fiscal\DTOs\CreateChargeDTO;
use App\Modules\Fiscal\Enums\FiscalEnvironment;
use App\Modules\Fiscal\Enums\FiscalInvoiceStatus;
use App\Modules\Fiscal\Enums\FiscalInvoiceType;
use App\Modules\Fiscal\Enums\PaymentGateway;
use App\Modules\Fiscal\Gateways\Adapters\AsaasAdapter;
use App\Modules\Fiscal\Gateways\Adapters\MercadoPagoAdapter;
use App\Modules\Fiscal\Gateways\Adapters\StripeAdapter;
use App\Modules\Fiscal\Jobs\EmitFiscalInvoiceJob;
use App\Modules\Fiscal\Models\FiscalInvoice;
use App\Modules\Fiscal\Models\PaymentGatewaySetting;
use App\Modules\Fiscal\Models\TenantTaxSetting;
use App\Modules\Fiscal\Models\WebhookEvent;
use App\Modules\Fiscal\Services\FiscalEmitterService;
use App\Modules\Fiscal\Services\WebhookService;
use Database\Seeders\CoreLandlordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class FiscalIntegrationTest extends TestCase
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

        $this->setTenantContext($this->tenantAlfa);
    }

    private function setTenantContext(Tenant $tenant): void
    {
        app(TenantContext::class)->set($tenant);
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("SELECT set_config('app.current_tenant_id', ?, false)", [$tenant->id]);
        }
    }

    public function test_vault_encryption_of_a1_certificate_password_and_gateway_api_key(): void
    {
        $this->setTenantContext($this->tenantAlfa);

        $taxSetting = TenantTaxSetting::create([
            'environment' => FiscalEnvironment::Homologation,
            'nfe_series' => 1,
            'next_nfe_number' => 100,
        ]);
        $taxSetting->setEncryptedCertificatePassword('SenhaSeguraCertA1@2026');
        $taxSetting->save();

        // Verifica que o dado no banco não está em texto plano
        $rawPassword = DB::table('tenant_tax_settings')->where('id', $taxSetting->id)->value('certificate_password');
        $this->assertNotEquals('SenhaSeguraCertA1@2026', $rawPassword);

        // Descriptografia válida para o Tenant Alfa
        $this->assertEquals('SenhaSeguraCertA1@2026', $taxSetting->getDecryptedCertificatePassword());

        // Credencial de Gateway
        $gatewaySetting = PaymentGatewaySetting::create([
            'gateway' => PaymentGateway::Asaas,
            'api_key' => 'temporario',
            'is_sandbox' => true,
        ]);
        $gatewaySetting->setEncryptedApiKey('$aact_YzA1NWQ2Y2M6MzI1');
        $gatewaySetting->setEncryptedWebhookSecret('webhook-sec-xyz-999');
        $gatewaySetting->save();

        $this->assertEquals('$aact_YzA1NWQ2Y2M6MzI1', $gatewaySetting->getDecryptedApiKey());
        $this->assertEquals('webhook-sec-xyz-999', $gatewaySetting->getDecryptedWebhookSecret());

        // Teste de violação de isolamento: Tenant Beta tentando descriptografar a chave de Alfa
        $vault = app(VaultEncryption::class);
        $this->expectException(CrossTenantTamperingException::class);
        $vault->decrypt((string) $gatewaySetting->api_key, $this->tenantBeta->id, 'gateway_api_key');
    }

    public function test_payment_gateway_adapter_generation_and_hmac_signature_verification(): void
    {
        $chargeDto = new CreateChargeDTO(
            amount: '150.00',
            description: 'Usinagem de Flange 4 Polegadas',
            dueDate: '2026-11-20',
            customerName: 'Cliente Sul Peças',
            customerDocument: '11222333000199',
            customerEmail: 'financeiro@sulpecas.com.br',
        );

        // 1. Asaas Adapter
        $asaas = new AsaasAdapter('asaas_key_test', true);
        $pixAsaas = $asaas->createPixCharge($chargeDto);
        $this->assertTrue($pixAsaas->success);
        $this->assertStringStartsWith('pay_asaas_pix_', $pixAsaas->transactionId);
        $this->assertNotNull($pixAsaas->pixCopiaECola);

        // Validação de assinatura Asaas (Token no header)
        $this->assertTrue($asaas->verifyWebhookSignature('', 'token-secreto-123', 'token-secreto-123'));
        $this->assertFalse($asaas->verifyWebhookSignature('', 'token-invalido', 'token-secreto-123'));

        // 2. Stripe Adapter
        $stripe = new StripeAdapter('sk_test_123', true);
        $cardStripe = $stripe->createCreditCardCharge($chargeDto);
        $this->assertTrue($cardStripe->success);
        $this->assertStringStartsWith('cs_stripe_card_', $cardStripe->transactionId);

        // Validação HMAC Stripe (t=timestamp,v1=hash)
        $payload = json_encode(['id' => 'evt_123', 'type' => 'payment_intent.succeeded']);
        $secret = 'whsec_stripe_test_secret';
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
        $header = "t={$timestamp},v1={$signature}";

        $this->assertTrue($stripe->verifyWebhookSignature((string) $payload, $header, $secret));
        $this->assertFalse($stripe->verifyWebhookSignature((string) $payload, "t={$timestamp},v1=invalido", $secret));

        // 3. Mercado Pago Adapter
        $mp = new MercadoPagoAdapter('mp_token_test', true);
        $boletoMp = $mp->createBoletoCharge($chargeDto);
        $this->assertTrue($boletoMp->success);
        $this->assertNotNull($boletoMp->boletoBarcode);

        // Validação HMAC Mercado Pago (ts=...,v1=...)
        $mpSecret = 'mp_secret_key_456';
        $mpTs = '1730000000';
        $mpHash = hash_hmac('sha256', "ts:{$mpTs}|data:{$payload}", $mpSecret);
        $mpHeader = "ts={$mpTs},v1={$mpHash}";

        $this->assertTrue($mp->verifyWebhookSignature((string) $payload, $mpHeader, $mpSecret));
        $this->assertFalse($mp->verifyWebhookSignature((string) $payload, "ts={$mpTs},v1=errado", $mpSecret));
    }

    public function test_webhook_idempotency_and_automated_financial_entry_settlement(): void
    {
        $this->setTenantContext($this->tenantAlfa);

        // Conta Bancária Ativa para receber o crédito
        $account = FinancialAccount::create([
            'name' => 'Conta Itaú Recebimentos',
            'type' => AccountType::Checking,
            'current_balance' => '1000.00',
            'is_default' => true,
        ]);

        // Plano de Contas
        $chart = ChartOfAccount::create([
            'code' => '3.1.50',
            'name' => 'Receita de Venda de Automação',
            'type' => AccountCategoryType::Revenue,
        ]);

        // Título a Receber em aberto: 350.00
        $entry = FinancialEntry::create([
            'entry_type' => EntryType::Receivable,
            'chart_of_account_id' => $chart->id,
            'document_number' => 'NF-AUTO-01',
            'title' => 'Painel de Automação Fabril',
            'original_amount' => '350.00',
            'balance_amount' => '350.00',
            'paid_amount' => '0.00',
            'installment_number' => 1,
            'total_installments' => 1,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-27',
            'competence_date' => '2026-10-01',
            'status' => EntryStatus::Pending,
        ]);

        // Configura Gateway Asaas com webhook_secret
        $gatewaySetting = PaymentGatewaySetting::create([
            'gateway' => PaymentGateway::Asaas,
            'api_key' => 'dummy',
            'is_sandbox' => true,
            'is_active' => true,
        ]);
        $gatewaySetting->setEncryptedApiKey('$aact_test_key');
        $gatewaySetting->setEncryptedWebhookSecret('webhook-token-seguro-asaas');
        $gatewaySetting->save();

        $webhookService = app(WebhookService::class);

        $payload = [
            'id' => 'evt_asaas_unique_9988',
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_asaas_001',
                'value' => 350.00,
                'externalReference' => $entry->id,
                'paymentDate' => '2026-10-27',
            ],
        ];

        // 1ª Execução do Webhook
        $result1 = $webhookService->handle(
            gateway: PaymentGateway::Asaas,
            rawPayload: (string) json_encode($payload),
            signatureHeader: 'webhook-token-seguro-asaas',
            payload: $payload,
        );

        $this->assertEquals('processed', $result1['status']);
        $this->assertTrue($result1['settled']);

        $entry->refresh();
        $account->refresh();

        // Título baixado automaticamente!
        $this->assertEquals('0.00', (string) $entry->balance_amount);
        $this->assertEquals('350.00', (string) $entry->paid_amount);
        $this->assertEquals(EntryStatus::Paid, $entry->status);

        // Saldo bancário atualizado: 1000.00 + 350.00 = 1350.00
        $this->assertEquals('1350.00', (string) $account->current_balance);

        // Registro de auditoria no webhook_events
        $this->assertDatabaseHas('webhook_events', [
            'external_event_id' => 'evt_asaas_unique_9988',
            'tenant_id' => $this->tenantAlfa->id,
            'gateway' => PaymentGateway::Asaas->value,
            'status' => 'PROCESSED',
        ]);

        // 2ª Execução com O MESMO evento (Simulação de retry do gateway)
        $result2 = $webhookService->handle(
            gateway: PaymentGateway::Asaas,
            rawPayload: (string) json_encode($payload),
            signatureHeader: 'webhook-token-seguro-asaas',
            payload: $payload,
        );

        $this->assertEquals('already_processed', $result2['status']);
        $this->assertFalse($result2['settled']);

        // Saldo não pode ser duplicado
        $account->refresh();
        $this->assertEquals('1350.00', (string) $account->current_balance);
        $this->assertCount(1, WebhookEvent::all());
    }

    public function test_asynchronous_fiscal_invoice_emission_job(): void
    {
        $this->setTenantContext($this->tenantAlfa);

        $partner = Partner::create([
            'partner_type' => PartnerType::Customer,
            'person_type' => PersonType::LegalEntity,
            'document_number' => '33444555000188',
            'name' => 'Indústria de Máquinas Joinville',
        ]);

        $taxSetting = TenantTaxSetting::create([
            'environment' => FiscalEnvironment::Homologation,
            'nfe_series' => 1,
            'next_nfe_number' => 45,
            'default_cfop' => '5101',
        ]);

        $invoice = FiscalInvoice::create([
            'partner_id' => $partner->id,
            'invoice_type' => FiscalInvoiceType::Nfe,
            'invoice_number' => 45,
            'series' => 1,
            'environment' => FiscalEnvironment::Homologation,
            'status' => FiscalInvoiceStatus::Draft,
            'total_amount' => '12500.00',
        ]);

        // Executa o Job
        $job = new EmitFiscalInvoiceJob($invoice->id, $this->tenantAlfa->id);
        $job->handle(app(FiscalEmitterService::class));

        $invoice->refresh();
        $taxSetting->refresh();

        // 1. Chave de acesso de 44 dígitos com Dígito Verificador válido
        $this->assertNotNull($invoice->access_key);
        $this->assertEquals(44, strlen($invoice->access_key));

        $emitter = app(FiscalEmitterService::class);
        $key43 = substr($invoice->access_key, 0, 43);
        $expectedDv = $emitter->calculateModulo11($key43);
        $this->assertEquals($expectedDv, (int) substr($invoice->access_key, -1));

        // 2. Status atualizado para AUTORIZADA
        $this->assertEquals(FiscalInvoiceStatus::Authorized, $invoice->status);
        $this->assertNotNull($invoice->protocol_number);
        $this->assertNotNull($invoice->authorized_at);

        // 3. XML e DANFE salvos com segurança no storage privado
        $this->assertNotNull($invoice->xml_path);
        $this->assertNotNull($invoice->pdf_path);
        Storage::disk('local')->assertExists($invoice->xml_path);
        Storage::disk('local')->assertExists($invoice->pdf_path);

        // 4. Próximo número de NF-e incrementado para 46
        $this->assertEquals(46, $taxSetting->next_nfe_number);
    }

    public function test_cross_tenant_isolation_on_fiscal_invoices_and_tax_settings(): void
    {
        $this->setTenantContext($this->tenantAlfa);

        $partnerAlfa = Partner::create([
            'partner_type' => PartnerType::Customer,
            'person_type' => PersonType::LegalEntity,
            'document_number' => '11222333000100',
            'name' => 'Cliente Exclusivo Alfa',
        ]);

        $invoiceAlfa = FiscalInvoice::create([
            'partner_id' => $partnerAlfa->id,
            'invoice_type' => FiscalInvoiceType::Nfe,
            'invoice_number' => 1,
            'series' => 1,
            'environment' => FiscalEnvironment::Homologation,
            'status' => FiscalInvoiceStatus::Authorized,
            'total_amount' => '3000.00',
            'access_key' => '35261011222333000100550010000000011123456784',
            'xml_path' => "fiscal/{$this->tenantAlfa->id}/nfe_alfa.xml",
        ]);

        Storage::disk('local')->put($invoiceAlfa->xml_path, '<xml>Alfa Confidencial</xml>');

        // Contexto Beta
        $this->setTenantContext($this->tenantBeta);

        $this->assertNull(FiscalInvoice::find($invoiceAlfa->id));
        $this->assertCount(0, FiscalInvoice::all());

        // IDOR via HTTP: Usuário Beta tenta baixar XML da NF-e de Alfa (404)
        $response = $this->actingAs($this->userBeta)
            ->withSession([
                'active_tenant_id' => $this->tenantBeta->id,
                'current_tenant_id' => $this->tenantBeta->id,
            ])
            ->get("/app/fiscal/invoices/{$invoiceAlfa->id}/download-xml");

        $response->assertStatus(404);
    }
}
