<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Tenancy\Models\Plan;
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
use App\Modules\Finance\Services\FinancialPaymentService;
use App\Modules\Integrations\Fiscal\Actions\PrepareInvoiceAction;
use App\Modules\Integrations\Models\PaymentGatewayConfig;
use App\Modules\Integrations\Models\TenantFiscalSetting;
use App\Modules\Inventory\Actions\ConsumeProductionOrderMaterialsAction;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\ItemCategory;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\KardexLedgerService;
use App\Modules\Production\Models\Bom;
use App\Modules\Production\Models\BomItem;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Production\Models\ProductionOrderMaterial;
use App\Modules\Production\Models\WorkCenter;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteConversionService;
use App\Modules\SalesOrders\Models\SalesOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FullSystemDemoSeeder extends Seeder
{
    /**
     * Popula um ecossistema completo e realista para uma Gráfica e Indústria de Brindes.
     */
    public function run(): void
    {
        // 0. Planos SaaS do Landlord
        $planStarter = Plan::firstOrCreate(
            ['slug' => 'grafica-starter'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Gráfica Starter',
                'description' => 'Ideal para gráficas rápidas e birôs com foco em orçamentos e CRM.',
                'price' => 199.00,
                'billing_interval' => 'monthly',
                'max_users' => 3,
                'max_monthly_orders' => 100,
                'features' => ['CRM & Funil Comercial', 'Orçamentos Gráficos Rápidos', 'Gestão de Clientes e Fornecedores'],
                'is_active' => true,
            ]
        );

        $planPro = Plan::firstOrCreate(
            ['slug' => 'pcp-grafica-pro'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'PCP & Gráfica Pro',
                'description' => 'Controle completo de engenharia gráfica (BOM), OPs e estoque imutável Kardex.',
                'price' => 499.00,
                'billing_interval' => 'monthly',
                'max_users' => 10,
                'max_monthly_orders' => 500,
                'features' => ['CRM & Funil Avançado', 'Orçamentos Gráficos (Chapas CTP / Tintas)', 'Fichas Técnicas (BOM)', 'Chão de Fábrica & OPs', 'Estoque Imutável Kardex (CMP)', 'Contas a Pagar/Receber & DRE'],
                'is_active' => true,
            ]
        );

        $planEnterprise = Plan::firstOrCreate(
            ['slug' => 'industria-brindes-enterprise'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Indústria & Brindes Enterprise',
                'description' => 'Para indústrias de brindes personalizados com automação fiscal e múltiplos depósitos.',
                'price' => 999.00,
                'billing_interval' => 'monthly',
                'max_users' => 50,
                'max_monthly_orders' => 5000,
                'features' => ['Tudo do Plano Pro', 'Múltiplos Depósitos/Almoxarifados', 'Emissão Fiscal NFe/NFSe Automatizada', 'Gateways de Pagamento & Webhooks', 'Auditoria Completa & SLA Prioritário'],
                'is_active' => true,
            ]
        );

        // 1. Criar Tenant vinculado ao plano Pro
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'plan_id' => $planPro->id,
            'name' => 'Gráfica & Brindes Express Ltda',
            'subdomain' => 'express-' . Str::random(5),
            'status' => 'active',
        ]);

        TenantContext::set($tenant);
        DefaultStatusesSeederService::seedForTenant($tenant->id);

        // 2. Criar Usuário Operador da Gráfica e SuperAdmin Landlord
        $user = User::forceCreate([
            'id' => (string) Str::uuid(),
            'name' => 'Diretor Operacional Express',
            'email' => 'operador@express.com',
            'password' => bcrypt('Operador123!'),
            'tenant_id' => $tenant->id,
            'is_super_admin' => false,
        ]);

        User::forceCreate([
            'id' => (string) Str::uuid(),
            'name' => 'SuperAdmin Landlord',
            'email' => 'admin@sgi-erp.com',
            'password' => bcrypt('Admin1234!'),
            'tenant_id' => null,
            'is_super_admin' => true,
        ]);

        // 3. Estrutura Financeira (Plano de Contas, Centros de Custo e Contas Bancárias)
        $revPrint = ChartOfAccount::create([
            'code' => '1.01.01',
            'name' => 'Receitas de Impressão Comercial / Catálogos',
            'type' => 'revenue',
        ]);

        $costMaterials = ChartOfAccount::create([
            'code' => '2.01.01',
            'name' => 'Custos de Matéria-Prima (Papéis e Chapas)',
            'type' => 'cost_of_goods',
        ]);

        $costCenterOffset = CostCenter::create([
            'code' => 'CC-OFFSET-01',
            'name' => 'Impressão Offset Plana',
        ]);

        $bankAccount = BankAccount::create([
            'name' => 'Banco Cora PJ - Conta Principal',
            'bank_code' => '403',
            'agency' => '0001',
            'account_number' => '1234567-8',
            'type' => 'checking',
            'initial_balance' => '25000.0000',
            'current_balance' => '25000.0000',
            'is_default' => true,
        ]);

        // 4. Clientes e Fornecedores
        $customer = Partner::create([
            'name' => 'Agência Publicidade Criativa Ltda',
            'document_number' => '12.345.678/0001-90',
            'type' => 'customer',
            'email' => 'contato@agenciacriativa.com',
            'phone' => '(11) 98765-4321',
        ]);

        $supplier = Partner::create([
            'name' => 'Distribuidora de Papéis Suzano Express',
            'document_number' => '98.765.432/0001-10',
            'type' => 'supplier',
            'email' => 'vendas@suzanoexpress.com',
        ]);

        // 5. Suprimentos & Estoque (Kardex Imutável)
        $warehouse = Warehouse::create([
            'name' => 'Almoxarifado Geral de Bobinas e Chapas',
            'code' => 'ALMOX-CENTRAL',
            'is_default' => true,
        ]);

        $categoryPaper = ItemCategory::create([
            'name' => 'Papéis Couché',
            'code' => 'PAP-COUCHE',
            'type' => 'raw_material',
        ]);

        $categoryCtp = ItemCategory::create([
            'name' => 'Chapas CTP Térmicas',
            'code' => 'CTP-PLATES',
            'type' => 'raw_material',
        ]);

        $coucheItem = Item::create([
            'category_id' => $categoryPaper->id,
            'code' => 'PAP-150-66X96',
            'name' => 'Papel Couché Fosco 150g Formato 66x96cm',
            'unit_of_measure' => 'FL',
            'standard_cost' => '0.8500',
            'min_stock' => '1000.0000',
        ]);

        $ctpItem = Item::create([
            'category_id' => $categoryCtp->id,
            'code' => 'CTP-745-605',
            'name' => 'Chapa CTP Térmica 745x605mm',
            'unit_of_measure' => 'UN',
            'standard_cost' => '32.0000',
            'min_stock' => '20.0000',
        ]);

        $kardexService = app(KardexLedgerService::class);

        // Carga inicial no Kardex
        $kardexService->recordMovement([
            'item_id' => $coucheItem->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'purchase_receipt',
            'direction' => 'in',
            'quantity' => '5000.0000',
            'unit_cost' => '0.8500',
            'notes' => 'Carga inicial de estoque de papéis',
        ]);

        $kardexService->recordMovement([
            'item_id' => $ctpItem->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'purchase_receipt',
            'direction' => 'in',
            'quantity' => '80.0000',
            'unit_cost' => '32.0000',
            'notes' => 'Carga inicial de chapas CTP',
        ]);

        // 6. Engenharia & Chão de Fábrica (BOM e OP)
        $bom = Bom::create([
            'code' => 'BOM-CAT-16P',
            'name' => 'Ficha Técnica: Catálogo A4 16 Páginas',
            'product_type' => 'commercial_print',
        ]);

        BomItem::create([
            'bom_id' => $bom->id,
            'name' => 'Papel Couché Fosco 150g',
            'item_type' => 'substrate',
            'quantity' => '2.5000',
            'unit_of_measure' => 'FL',
        ]);

        $workCenter = WorkCenter::create([
            'name' => 'Impressora Offset Heidelberg 4 Cores',
            'code' => 'OFF-HEIDELBERG-01',
            'type' => 'offset_printing',
            'hourly_rate' => '250.0000',
        ]);

        $prodStatus = TenantStatus::forModule('production_orders')->firstOrFail();

        $op = ProductionOrder::create([
            'order_number' => 'OP-EXPRESS-2026-001',
            'product_name' => 'Catálogo Comercial 16 Páginas Couché',
            'status_id' => $prodStatus->id,
            'bom_id' => $bom->id,
            'target_quantity' => 1000,
            'produced_quantity' => 1000,
        ]);

        $opMaterial = ProductionOrderMaterial::create([
            'production_order_id' => $op->id,
            'name' => $coucheItem->name,
            'item_type' => 'raw_material',
            'planned_quantity' => '2500.0000',
            'consumed_quantity' => '0.0000',
            'unit_of_measure' => 'FL',
            'status' => 'reserved',
        ]);

        // Baixa de insumo consumido na OP
        $consumeAction = app(ConsumeProductionOrderMaterialsAction::class);
        $consumeAction->execute($op, $opMaterial, '2500.0000', $warehouse->id, $user->id);

        // 7. Orçamento e Conversão em Pedido de Venda
        $quoteStatus = TenantStatus::forModule('quotes')->firstOrFail();

        $quote = Quote::create([
            'customer_id' => $customer->id,
            'status_id' => $quoteStatus->id,
            'title' => 'Catálogo Comercial 16 Páginas',
            'quote_number' => 'ORC-EXPRESS-001',
            'valid_until' => now()->addDays(15)->toDateString(),
            'subtotal' => '4800.0000',
            'total_amount' => '4800.0000',
            'estimated_cost' => '2400.0000',
            'target_margin_percent' => '50.0000',
        ]);

        $quote->items()->create([
            'product_type' => 'commercial_print',
            'name' => 'Catálogo A4 16 Páginas Institucional',
            'quantity' => 1000,
            'format_open' => '420x297 mm',
            'format_closed' => '210x297 mm',
            'substrate_type' => 'Papel Couché Fosco 150g',
            'colors_front' => 4,
            'colors_back' => 4,
            'ctp_plates_count' => 8,
            'unit_cost' => '2.4000',
            'markup_multiplier' => '2.0000',
            'unit_price' => '4.8000',
            'total_price' => '4800.0000',
        ]);

        // Conversão em Pedido de Venda
        $conversion = QuoteConversionService::convert($quote, 'PV-EXPRESS-0001');
        /** @var SalesOrder $salesOrder */
        $salesOrder = $conversion['sales_order'];

        // 8. Financeiro: Título de Contas a Receber gerado pelo Pedido
        $receivable = FinancialEntry::create([
            'type' => 'receivable',
            'entry_number' => 'REC-PV-EXPRESS-0001',
            'partner_id' => $customer->id,
            'chart_of_account_id' => $revPrint->id,
            'cost_center_id' => $costCenterOffset->id,
            'bank_account_id' => $bankAccount->id,
            'status' => 'pending',
            'description' => 'Faturamento PV-EXPRESS-0001 (1.000 Catálogos A4)',
            'original_amount' => '4800.0000',
            'net_amount' => '4800.0000',
            'paid_amount' => '0.0000',
            'balance_due' => '4800.0000',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'competence_date' => now()->toDateString(),
            'installment_number' => 1,
            'total_installments' => 1,
            'reference_type' => 'sales_orders',
            'reference_id' => $salesOrder->id,
        ]);

        // Baixa parcial via PIX (R$ 2.400,00 de entrada)
        $paymentService = app(FinancialPaymentService::class);
        $paymentService->recordPayment($receivable, [
            'amount' => '2400.0000',
            'bank_account_id' => $bankAccount->id,
            'payment_method' => 'pix',
            'notes' => 'Sinal de 50% via PIX confirmado',
        ]);

        // 9. Configurações Fiscais e Emissão de NFe
        $fiscalSettings = TenantFiscalSetting::create([
            'cnpj' => '12.345.678/0001-90',
            'state_registration' => '111.222.333.444',
            'tax_regime' => 'simples_nacional',
            'environment' => 'sandbox',
            'next_nfe_number' => 101,
            'nfe_series' => 1,
            'default_ncm' => '49111090',
            'default_cfop' => '5101',
        ]);

        $fiscalSettings->setCertificateA1('MOCK_PFX_CERTIFICATE_CONTENT_ENCRYPTED_VAULT', 'senha_a1_secreta');
        $fiscalSettings->save();

        // Configuração de Gateway Asaas no Cofre
        $gwConfig = PaymentGatewayConfig::create([
            'gateway_name' => 'asaas',
            'is_active' => true,
            'mode' => 'sandbox',
        ]);
        $gwConfig->setApiKey('mock_asaas_api_key_vault_secret');
        $gwConfig->setWebhookSecret('secret_test_token');
        $gwConfig->save();

        // Emissão da NFe
        $prepareAction = app(PrepareInvoiceAction::class);
        $prepareAction->execute($salesOrder, $customer, 'nfe');
    }
}
