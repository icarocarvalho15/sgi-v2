<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\CRM\Enums\PartnerType;
use App\Modules\CRM\Enums\PersonType;
use App\Modules\CRM\Models\Partner;
use App\Modules\Finance\Actions\ReverseFinancialPaymentAction;
use App\Modules\Finance\Actions\SettleFinancialPaymentAction;
use App\Modules\Finance\DTOs\CreateFinancialEntryDTO;
use App\Modules\Finance\DTOs\SettleFinancialPaymentDTO;
use App\Modules\Finance\Enums\AccountCategoryType;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\EntryStatus;
use App\Modules\Finance\Enums\EntryType;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\CostCenter;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Services\CashFlowReportService;
use App\Modules\Finance\Services\DreReportService;
use App\Modules\Finance\Services\FinancialEntryService;
use Database\Seeders\CoreLandlordSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class FinanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantAlfa;

    private Tenant $tenantBeta;

    private User $userAlfa;

    private User $userBeta;

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

        // Contexto Alfa inicial
        $this->setTenantContext($this->tenantAlfa);
    }

    private function setTenantContext(Tenant $tenant): void
    {
        app(TenantContext::class)->set($tenant);
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("SELECT set_config('app.current_tenant_id', ?, false)", [$tenant->id]);
        }
    }

    public function test_financial_accounts_chart_and_cost_centers_isolation(): void
    {
        $this->setTenantContext($this->tenantAlfa);

        $accountAlfa = FinancialAccount::create([
            'name' => 'Itaú Conta Corrente Alfa',
            'type' => AccountType::Checking,
            'bank_name' => 'Banco Itaú',
            'agency_number' => '1234',
            'account_number' => '99887-1',
            'current_balance' => '5000.00',
            'is_default' => true,
        ]);

        $chartAlfa = ChartOfAccount::create([
            'code' => '3.1.01',
            'name' => 'Receita de Venda de Peças Usinadas',
            'type' => AccountCategoryType::Revenue,
        ]);

        $costCenterAlfa = CostCenter::create([
            'code' => 'CC-USIN-01',
            'name' => 'Usinagem Pesada Alfa',
            'description' => 'Tornos CNC e Fresadoras',
        ]);

        $this->assertDatabaseHas('financial_accounts', [
            'id' => $accountAlfa->id,
            'tenant_id' => $this->tenantAlfa->id,
            'name' => 'Itaú Conta Corrente Alfa',
        ]);

        // Contexto Beta
        $this->setTenantContext($this->tenantBeta);

        $this->assertNull(FinancialAccount::find($accountAlfa->id));
        $this->assertNull(ChartOfAccount::find($chartAlfa->id));
        $this->assertNull(CostCenter::find($costCenterAlfa->id));

        $this->assertCount(0, FinancialAccount::all());
        $this->assertCount(0, ChartOfAccount::all());
        $this->assertCount(0, CostCenter::all());
    }

    public function test_installment_entry_creation_and_penny_distribution(): void
    {
        $this->setTenantContext($this->tenantAlfa);

        $chart = ChartOfAccount::create([
            'code' => '3.1.02',
            'name' => 'Vendas Gerais',
            'type' => AccountCategoryType::Revenue,
        ]);

        $partner = Partner::create([
            'partner_type' => PartnerType::Customer,
            'person_type' => PersonType::LegalEntity,
            'document_number' => '11222333000199',
            'name' => 'Cliente Metal Mecânica Sul',
        ]);

        $dto = new CreateFinancialEntryDTO(
            entryType: EntryType::Receivable,
            chartOfAccountId: $chart->id,
            documentNumber: 'NF-7001',
            title: 'Fornecimento de Eixos Especiais',
            totalAmount: '100.00',
            firstDueDate: '2026-11-10',
            partnerId: $partner->id,
            installmentsCount: 3,
            intervalDays: 30,
        );

        $service = app(FinancialEntryService::class);
        $entries = $service->createWithInstallments($dto);

        $this->assertCount(3, $entries);

        // 100.00 / 3 = 33.33 com 0.01 de sobra distribuído na 1ª parcela
        $this->assertEquals('33.34', (string) $entries[0]->original_amount);
        $this->assertEquals('33.34', (string) $entries[0]->balance_amount);
        $this->assertEquals('NF-7001-1/3', $entries[0]->document_number);
        $this->assertEquals('2026-11-10', $entries[0]->due_date->toDateString());

        $this->assertEquals('33.33', (string) $entries[1]->original_amount);
        $this->assertEquals('33.33', (string) $entries[1]->balance_amount);
        $this->assertEquals('NF-7001-2/3', $entries[1]->document_number);
        $this->assertEquals('2026-12-10', $entries[1]->due_date->toDateString());

        $this->assertEquals('33.33', (string) $entries[2]->original_amount);
        $this->assertEquals('33.33', (string) $entries[2]->balance_amount);
        $this->assertEquals('NF-7001-3/3', $entries[2]->document_number);
        $this->assertEquals('2027-01-09', $entries[2]->due_date->toDateString());

        // A soma exata deve ser 100.00
        $sum = bcadd(bcadd((string) $entries[0]->original_amount, (string) $entries[1]->original_amount, 2), (string) $entries[2]->original_amount, 2);
        $this->assertEquals('100.00', $sum);
    }

    public function test_partial_and_full_payment_settlement_with_interest_and_discount(): void
    {
        $this->setTenantContext($this->tenantAlfa);

        $account = FinancialAccount::create([
            'name' => 'Banco Bradesco Principal',
            'type' => AccountType::Checking,
            'current_balance' => '500.00',
        ]);

        $chart = ChartOfAccount::create([
            'code' => '3.1.03',
            'name' => 'Serviços de Corte a Laser',
            'type' => AccountCategoryType::Revenue,
        ]);

        $entry = FinancialEntry::create([
            'entry_type' => EntryType::Receivable,
            'chart_of_account_id' => $chart->id,
            'document_number' => 'DOC-LASER-01',
            'title' => 'Corte a Laser Chapas Inox',
            'original_amount' => '1000.00',
            'balance_amount' => '1000.00',
            'paid_amount' => '0.00',
            'interest_amount' => '0.00',
            'fine_amount' => '0.00',
            'discount_amount' => '0.00',
            'installment_number' => 1,
            'total_installments' => 1,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-25',
            'competence_date' => '2026-10-01',
            'status' => EntryStatus::Pending,
        ]);

        $settleAction = app(SettleFinancialPaymentAction::class);

        // 1. Baixa Parcial de 400.00 com Juros de 20.00, Multa de 10.00 e Desconto de 5.00
        // Líquido esperado = 400 + 20 + 10 - 5 = 425.00
        $payment1 = $settleAction->execute(new SettleFinancialPaymentDTO(
            financialEntryId: $entry->id,
            financialAccountId: $account->id,
            paymentMethod: PaymentMethod::Pix,
            paymentDate: '2026-10-26',
            amount: '400.00',
            interestPaid: '20.00',
            finePaid: '10.00',
            discountApplied: '5.00',
            notes: 'Baixa parcial via PIX com juros de atraso',
        ));

        $this->assertEquals('425.00', (string) $payment1->net_amount);

        $entry->refresh();
        $account->refresh();

        $this->assertEquals('600.00', (string) $entry->balance_amount);
        $this->assertEquals('400.00', (string) $entry->paid_amount);
        $this->assertEquals('20.00', (string) $entry->interest_amount);
        $this->assertEquals('10.00', (string) $entry->fine_amount);
        $this->assertEquals('5.00', (string) $entry->discount_amount);
        $this->assertEquals(EntryStatus::PartiallyPaid, $entry->status);

        // Saldo da conta: 500.00 + 425.00 = 925.00
        $this->assertEquals('925.00', (string) $account->current_balance);

        // 2. Baixa Total do restante de 600.00
        $payment2 = $settleAction->execute(new SettleFinancialPaymentDTO(
            financialEntryId: $entry->id,
            financialAccountId: $account->id,
            paymentMethod: PaymentMethod::BankTransfer,
            paymentDate: '2026-10-27',
            amount: '600.00',
        ));

        $entry->refresh();
        $account->refresh();

        $this->assertEquals('0.00', (string) $entry->balance_amount);
        $this->assertEquals('1000.00', (string) $entry->paid_amount);
        $this->assertEquals(EntryStatus::Paid, $entry->status);

        // Saldo da conta: 925.00 + 600.00 = 1525.00
        $this->assertEquals('1525.00', (string) $account->current_balance);
    }

    public function test_payment_reversal_restores_entry_and_account_balances(): void
    {
        $this->setTenantContext($this->tenantAlfa);

        $account = FinancialAccount::create([
            'name' => 'Conta Santander',
            'type' => AccountType::Checking,
            'current_balance' => '1000.00',
        ]);

        $chart = ChartOfAccount::create([
            'code' => '4.1.01',
            'name' => 'Despesas de Manutenção Predial',
            'type' => AccountCategoryType::Expense,
        ]);

        // Título a Pagar de 300.00
        $entry = FinancialEntry::create([
            'entry_type' => EntryType::Payable,
            'chart_of_account_id' => $chart->id,
            'document_number' => 'FAT-MANUT-99',
            'title' => 'Manutenção Preventiva Compressores',
            'original_amount' => '300.00',
            'balance_amount' => '300.00',
            'paid_amount' => '0.00',
            'installment_number' => 1,
            'total_installments' => 1,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-27',
            'competence_date' => '2026-10-01',
            'status' => EntryStatus::Pending,
        ]);

        $settleAction = app(SettleFinancialPaymentAction::class);
        $payment = $settleAction->execute(new SettleFinancialPaymentDTO(
            financialEntryId: $entry->id,
            financialAccountId: $account->id,
            paymentMethod: PaymentMethod::Pix,
            paymentDate: '2026-10-27',
            amount: '300.00',
        ));

        $entry->refresh();
        $account->refresh();

        $this->assertEquals(EntryStatus::Paid, $entry->status);
        $this->assertEquals('0.00', (string) $entry->balance_amount);
        // Despesa a pagar reduz o saldo da conta: 1000.00 - 300.00 = 700.00
        $this->assertEquals('700.00', (string) $account->current_balance);

        // Estorno
        $reverseAction = app(ReverseFinancialPaymentAction::class);
        $reversed = $reverseAction->execute($payment->id, 'Erro na digitação da conta de débito');

        $this->assertTrue($reversed->is_reversed);
        $this->assertEquals('Erro na digitação da conta de débito', $reversed->reversal_reason);
        $this->assertNotNull($reversed->reversed_at);

        $entry->refresh();
        $account->refresh();

        // Saldos restaurados: título volta a ter 300.00 pendente e status Pendente
        $this->assertEquals('300.00', (string) $entry->balance_amount);
        $this->assertEquals('0.00', (string) $entry->paid_amount);
        $this->assertEquals(EntryStatus::Pending, $entry->status);

        // Conta bancária estorna a saída de 300.00: 700.00 + 300.00 = 1000.00
        $this->assertEquals('1000.00', (string) $account->current_balance);

        // Tentar estornar novamente deve lançar DomainException
        $this->expectException(DomainException::class);
        $reverseAction->execute($payment->id, 'Segunda tentativa inválida');
    }

    public function test_cash_flow_and_dre_reporting_services(): void
    {
        $this->setTenantContext($this->tenantAlfa);

        $account = FinancialAccount::create([
            'name' => 'Banco do Brasil',
            'type' => AccountType::Checking,
            'current_balance' => '2000.00',
        ]);

        $revChart = ChartOfAccount::create([
            'code' => '3.1.10',
            'name' => 'Vendas Indústria Alfa',
            'type' => AccountCategoryType::Revenue,
        ]);

        $expChart = ChartOfAccount::create([
            'code' => '4.2.10',
            'name' => 'Energia Elétrica Fábrica',
            'type' => AccountCategoryType::Expense,
        ]);

        // Entrada a Receber Realizada: 1500.00
        $recEntry = FinancialEntry::create([
            'entry_type' => EntryType::Receivable,
            'chart_of_account_id' => $revChart->id,
            'document_number' => 'NF-REC-01',
            'title' => 'Receita Produto Acabado',
            'original_amount' => '1500.00',
            'balance_amount' => '1500.00',
            'paid_amount' => '0.00',
            'installment_number' => 1,
            'total_installments' => 1,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'competence_date' => '2026-10-01',
            'status' => EntryStatus::Pending,
        ]);

        $settleAction = app(SettleFinancialPaymentAction::class);
        $settleAction->execute(new SettleFinancialPaymentDTO(
            financialEntryId: $recEntry->id,
            financialAccountId: $account->id,
            paymentMethod: PaymentMethod::Pix,
            paymentDate: '2026-10-15',
            amount: '1500.00',
        ));

        // Saída a Pagar Realizada: 500.00
        $payEntry = FinancialEntry::create([
            'entry_type' => EntryType::Payable,
            'chart_of_account_id' => $expChart->id,
            'document_number' => 'FAT-LUZ-01',
            'title' => 'Conta de Energia Elétrica',
            'original_amount' => '500.00',
            'balance_amount' => '500.00',
            'paid_amount' => '0.00',
            'installment_number' => 1,
            'total_installments' => 1,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-20',
            'competence_date' => '2026-10-01',
            'status' => EntryStatus::Pending,
        ]);

        $settleAction->execute(new SettleFinancialPaymentDTO(
            financialEntryId: $payEntry->id,
            financialAccountId: $account->id,
            paymentMethod: PaymentMethod::Boleto,
            paymentDate: '2026-10-20',
            amount: '500.00',
        ));

        // Título Futuro a Receber (Projetado): 800.00
        FinancialEntry::create([
            'entry_type' => EntryType::Receivable,
            'chart_of_account_id' => $revChart->id,
            'document_number' => 'NF-FUT-01',
            'title' => 'Venda Futura Prevista',
            'original_amount' => '800.00',
            'balance_amount' => '800.00',
            'paid_amount' => '0.00',
            'installment_number' => 1,
            'total_installments' => 1,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-30',
            'competence_date' => '2026-10-01',
            'status' => EntryStatus::Pending,
        ]);

        // 1. Relatório de Fluxo de Caixa
        $cashFlowService = app(CashFlowReportService::class);
        $cf = $cashFlowService->generate('2026-10-01', '2026-10-31');

        $this->assertEquals('1500.00', $cf['realized_inflow']);
        $this->assertEquals('500.00', $cf['realized_outflow']);
        $this->assertEquals('800.00', $cf['projected_inflow']);
        $this->assertEquals('0.00', $cf['projected_outflow']);

        // 2. Relatório DRE - Regime de Caixa
        $dreService = app(DreReportService::class);
        $dreCash = $dreService->generate('cash', '2026-10-01', '2026-10-31');

        $this->assertEquals('1500.00', $dreCash['gross_revenue']);
        $this->assertEquals('500.00', $dreCash['total_expenses']);
        $this->assertEquals('1000.00', $dreCash['net_result']);

        // 3. Relatório DRE - Regime de Competência (inclui a venda futura de 800)
        $dreComp = $dreService->generate('competence', '2026-10-01', '2026-10-31');
        $this->assertEquals('2300.00', $dreComp['gross_revenue']); // 1500 + 800
        $this->assertEquals('500.00', $dreComp['total_expenses']);
        $this->assertEquals('1800.00', $dreComp['net_result']);
    }

    public function test_cross_tenant_idor_and_foreign_key_protection(): void
    {
        $this->setTenantContext($this->tenantAlfa);

        $accountAlfa = FinancialAccount::create([
            'name' => 'Conta Secreta Alfa',
            'type' => AccountType::Checking,
            'current_balance' => '10000.00',
        ]);

        $chartAlfa = ChartOfAccount::create([
            'code' => '3.9.99',
            'name' => 'Receita Especial Alfa',
            'type' => AccountCategoryType::Revenue,
        ]);

        $entryAlfa = FinancialEntry::create([
            'entry_type' => EntryType::Receivable,
            'chart_of_account_id' => $chartAlfa->id,
            'document_number' => 'DOC-SECRET-01',
            'title' => 'Recebimento Confidencial Alfa',
            'original_amount' => '5000.00',
            'balance_amount' => '5000.00',
            'paid_amount' => '0.00',
            'installment_number' => 1,
            'total_installments' => 1,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-11-01',
            'competence_date' => '2026-10-01',
            'status' => EntryStatus::Pending,
        ]);

        // Simula login de User Beta no Tenant Beta
        $response = $this->actingAs($this->userBeta)
            ->withSession([
                'active_tenant_id' => $this->tenantBeta->id,
                'current_tenant_id' => $this->tenantBeta->id,
            ])
            ->get("/app/finance/entries/{$entryAlfa->id}");

        // IDOR: Beta não pode acessar títulos de Alfa (404)
        $response->assertStatus(404);

        // Beta não pode registrar baixa no título de Alfa (404)
        $settleResponse = $this->actingAs($this->userBeta)
            ->withSession([
                'active_tenant_id' => $this->tenantBeta->id,
                'current_tenant_id' => $this->tenantBeta->id,
            ])
            ->post("/app/finance/entries/{$entryAlfa->id}/settle", [
                'financial_account_id' => $accountAlfa->id,
                'payment_method' => 'PIX',
                'payment_date' => '2026-11-01',
                'amount' => '100.00',
            ]);

        $settleResponse->assertStatus(404);

        // Beta não pode injetar chart_of_account_id de Alfa na criação de um título (422)
        $createResponse = $this->actingAs($this->userBeta)
            ->withSession([
                'active_tenant_id' => $this->tenantBeta->id,
                'current_tenant_id' => $this->tenantBeta->id,
            ])
            ->post('/app/finance/entries', [
                'entry_type' => 'RECEIVABLE',
                'chart_of_account_id' => $chartAlfa->id,
                'title' => 'Título Invasivo Beta',
                'total_amount' => '100.00',
                'first_due_date' => '2026-11-15',
            ]);

        $createResponse->assertStatus(302);
        $createResponse->assertSessionHasErrors(['chart_of_account_id']);
    }
}
