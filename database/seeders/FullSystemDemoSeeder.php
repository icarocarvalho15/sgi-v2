<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Security\VaultEncryption;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\CRM\Actions\ConvertQuoteToSalesOrderAction;
use App\Modules\CRM\Enums\AddressType;
use App\Modules\CRM\Enums\DealPriority;
use App\Modules\CRM\Enums\DealStatus;
use App\Modules\CRM\Enums\PartnerType;
use App\Modules\CRM\Enums\PersonType;
use App\Modules\CRM\Enums\QuoteStatus;
use App\Modules\CRM\Models\CrmDeal;
use App\Modules\CRM\Models\CrmDealStage;
use App\Modules\CRM\Models\CrmPipeline;
use App\Modules\CRM\Models\Partner;
use App\Modules\CRM\Models\PartnerAddress;
use App\Modules\CRM\Models\Quote;
use App\Modules\CRM\Models\QuoteItem;
use App\Modules\Finance\Actions\SettleFinancialPaymentAction;
use App\Modules\Finance\DTOs\CreateFinancialEntryDTO;
use App\Modules\Finance\DTOs\SettleFinancialPaymentDTO;
use App\Modules\Finance\Enums\AccountCategoryType;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\EntryType;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\CostCenter;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Services\FinancialEntryService;
use App\Modules\Fiscal\Enums\FiscalEnvironment;
use App\Modules\Fiscal\Enums\FiscalInvoiceStatus;
use App\Modules\Fiscal\Enums\FiscalInvoiceType;
use App\Modules\Fiscal\Enums\PaymentGateway;
use App\Modules\Fiscal\Enums\TaxRegime;
use App\Modules\Fiscal\Enums\WebhookEventStatus;
use App\Modules\Fiscal\Models\FiscalInvoice;
use App\Modules\Fiscal\Models\PaymentGatewaySetting;
use App\Modules\Fiscal\Models\TenantTaxSetting;
use App\Modules\Fiscal\Models\WebhookEvent;
use App\Modules\Fiscal\Services\FiscalEmitterService;
use App\Modules\Inventory\DTOs\StockMovementDTO;
use App\Modules\Inventory\Enums\KardexMovementType;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\UnitOfMeasure;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\KardexLedgerService;
use App\Modules\Production\DTOs\RecordProductionDTO;
use App\Modules\Production\Models\Bom;
use App\Modules\Production\Models\BomItem;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Production\Models\RoutingStep;
use App\Modules\Production\Models\WorkCenter;
use App\Modules\Production\Services\ProductionOrderService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class FullSystemDemoSeeder extends Seeder
{
    /**
     * Run the comprehensive system demo and diagnostic seeder.
     */
    public function run(): void
    {
        // 1. Executa a estrutura base Landlord e Tenants Alfa/Beta
        $this->call(CoreLandlordSeeder::class);

        /** @var Tenant $tenantAlfa */
        $tenantAlfa = Tenant::query()->where('subdomain', 'alfa')->firstOrFail();

        /** @var Tenant $tenantBeta */
        $tenantBeta = Tenant::query()->where('subdomain', 'beta')->firstOrFail();

        /** @var User $userAlfa */
        $userAlfa = User::query()->where('email', 'alfa.admin@metalurgicaalfa.com.br')->firstOrFail();

        // =========================================================================
        // SEEDING TENANT ALFA (Metalúrgica Alfa Ltda - Indústria Completa)
        // =========================================================================
        $this->setTenantContext($tenantAlfa);

        // 2. Unidades de Medida e Almoxarifados
        $uomKg = UnitOfMeasure::query()->where('code', 'KG')->firstOrFail();
        $uomUn = UnitOfMeasure::query()->where('code', 'UN')->firstOrFail();
        $uomM2 = UnitOfMeasure::query()->where('code', 'M2')->firstOrFail();

        /** @var Warehouse $warehouseRaw */
        $warehouseRaw = Warehouse::query()->where('code', 'ALM-01')->firstOrFail();

        $warehouseFinished = Warehouse::query()->firstOrCreate(
            ['code' => 'ALM-PA', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Depósito de Produtos Acabados',
                'description' => 'Armazenamento de máquinas e redutores prontos para expedição',
                'is_active' => true,
                'is_default' => false,
            ]
        );

        // 3. CRM: Clientes e Fornecedores B2B
        $customer = Partner::query()->firstOrCreate(
            ['document_number' => '12.888.999/0001-44', 'tenant_id' => $tenantAlfa->id],
            [
                'partner_type' => PartnerType::Customer,
                'person_type' => PersonType::LegalEntity,
                'name' => 'Indústria Automotiva Catarinense S/A',
                'trade_name' => 'AutoCatarinense Linhas de Montagem',
                'state_registration' => '254.999.888',
                'email' => 'compras@autocatarinense.com.br',
                'phone' => '(47) 3456-7890',
                'notes' => 'Cliente B2B Tier-1 da indústria automobilística. Pagamentos pontuais.',
                'is_active' => true,
            ]
        );

        PartnerAddress::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id, 'partner_id' => $customer->id, 'address_type' => AddressType::Billing],
            [
                'street' => 'Rua dos Engenheiros',
                'number' => '1500',
                'complement' => 'Galpão 4',
                'district' => 'Distrito Industrial',
                'city' => 'Joinville',
                'state' => 'SC',
                'postal_code' => '89200-000',
                'country' => 'BR',
                'is_default' => true,
            ]
        );

        PartnerAddress::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id, 'partner_id' => $customer->id, 'address_type' => AddressType::Shipping],
            [
                'street' => 'Rua dos Engenheiros',
                'number' => '1500',
                'complement' => 'Portaria 2 - Recebimento',
                'district' => 'Distrito Industrial',
                'city' => 'Joinville',
                'state' => 'SC',
                'postal_code' => '89200-000',
                'country' => 'BR',
                'is_default' => false,
            ]
        );

        $supplier = Partner::query()->firstOrCreate(
            ['document_number' => '44.555.666/0001-22', 'tenant_id' => $tenantAlfa->id],
            [
                'partner_type' => PartnerType::Supplier,
                'person_type' => PersonType::LegalEntity,
                'name' => 'Siderúrgica Gerdau Aços Forjados S/A',
                'trade_name' => 'Gerdau Aços Especiais',
                'state_registration' => '111.222.333',
                'email' => 'vendas@gerdauforjados.com.br',
                'phone' => '(11) 4000-1122',
                'notes' => 'Fornecedor homologado de barras laminadas 1020 e chapas de aço inox.',
                'is_active' => true,
            ]
        );

        PartnerAddress::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id, 'partner_id' => $supplier->id, 'address_type' => AddressType::Billing],
            [
                'street' => 'Avenida das Siderúrgicas',
                'number' => '500',
                'district' => 'Pólo Metalmecânico',
                'city' => 'Porto Alegre',
                'state' => 'RS',
                'postal_code' => '90000-000',
                'country' => 'BR',
                'is_default' => true,
            ]
        );

        // 4. CRM: Pipeline e Fases do Funil Kanban
        /** @var CrmPipeline $pipeline */
        $pipeline = CrmPipeline::query()->firstOrCreate(
            ['name' => 'Vendas Industriais Sob Encomenda', 'tenant_id' => $tenantAlfa->id],
            [
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $stagesConfig = [
            ['name' => 'Prospecção', 'sequence' => 1, 'probability_percentage' => '10.00', 'color' => '#94a3b8', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Qualificação Técnica', 'sequence' => 2, 'probability_percentage' => '30.00', 'color' => '#60a5fa', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Orçamento Técnico', 'sequence' => 3, 'probability_percentage' => '50.00', 'color' => '#f59e0b', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Negociação Comercial', 'sequence' => 4, 'probability_percentage' => '70.00', 'color' => '#a855f7', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Fechado/Ganho', 'sequence' => 5, 'probability_percentage' => '100.00', 'color' => '#10b981', 'is_won' => true, 'is_lost' => false],
            ['name' => 'Perdido', 'sequence' => 6, 'probability_percentage' => '0.00', 'color' => '#ef4444', 'is_won' => false, 'is_lost' => true],
        ];

        /** @var array<string, CrmDealStage> $stages */
        $stages = [];
        foreach ($stagesConfig as $sc) {
            $stage = CrmDealStage::query()->firstOrCreate(
                ['pipeline_id' => $pipeline->id, 'name' => $sc['name'], 'tenant_id' => $tenantAlfa->id],
                $sc
            );
            $stages[$sc['name']] = $stage;
        }

        // Oportunidades (Deals) no Funil
        $dealWon = CrmDeal::query()->firstOrCreate(
            ['title' => 'Fornecimento de 10 Redutores RV-50 para Linha Montagem', 'tenant_id' => $tenantAlfa->id],
            [
                'pipeline_id' => $pipeline->id,
                'stage_id' => $stages['Fechado/Ganho']->id,
                'partner_id' => $customer->id,
                'estimated_value' => '5166.00',
                'expected_close_date' => Carbon::now()->toDateString(),
                'status' => DealStatus::Won,
                'priority' => DealPriority::High,
                'assigned_user_id' => $userAlfa->id,
                'notes' => 'Oportunidade convertida com sucesso em Pedido e Ordem de Produção.',
            ]
        );

        CrmDeal::query()->firstOrCreate(
            ['title' => 'Usinagem Especial de Flanges em Inox 304', 'tenant_id' => $tenantAlfa->id],
            [
                'pipeline_id' => $pipeline->id,
                'stage_id' => $stages['Negociação Comercial']->id,
                'partner_id' => $customer->id,
                'estimated_value' => '15400.00',
                'expected_close_date' => Carbon::now()->addDays(20)->toDateString(),
                'status' => DealStatus::Open,
                'priority' => DealPriority::Medium,
                'assigned_user_id' => $userAlfa->id,
                'notes' => 'Aguardando validação do departamento de engenharia do cliente.',
            ]
        );

        // 5. Engenharia & Suprimentos: Insumos de Matéria-Prima e Produto Acabado
        $itemBar1020 = Item::query()->firstOrCreate(
            ['sku' => 'MP-BAR-1020', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Barra Redonda Aço SAE 1020 2 pol',
                'description' => 'Aço carbono trefilado para eixos usinados',
                'unit_of_measure_id' => $uomKg->id,
                'default_warehouse_id' => $warehouseRaw->id,
                'is_raw_material' => true,
                'is_manufactured' => false,
                'is_sellable' => false,
                'is_service' => false,
                'allow_negative_stock' => false,
                'min_stock' => '50.0000',
                'average_cost' => '12.0000',
                'last_cost' => '12.0000',
                'sale_price' => '0.00',
                'is_active' => true,
            ]
        );

        $itemChapaInox = Item::query()->firstOrCreate(
            ['sku' => 'MP-CHP-304', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Chapa Aço Inox AISI 304 3mm',
                'description' => 'Chapa laminada a quente para carcaças de redutores',
                'unit_of_measure_id' => $uomM2->id,
                'default_warehouse_id' => $warehouseRaw->id,
                'is_raw_material' => true,
                'is_manufactured' => false,
                'is_sellable' => false,
                'is_service' => false,
                'allow_negative_stock' => false,
                'min_stock' => '20.0000',
                'average_cost' => '35.0000',
                'last_cost' => '35.0000',
                'sale_price' => '0.00',
                'is_active' => true,
            ]
        );

        $itemParafuso = Item::query()->firstOrCreate(
            ['sku' => 'MP-PAR-M10', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Parafuso Sextavado Aço 8.8 M10x50mm',
                'description' => 'Fixadores zincados de alta resistência',
                'unit_of_measure_id' => $uomUn->id,
                'default_warehouse_id' => $warehouseRaw->id,
                'is_raw_material' => true,
                'is_manufactured' => false,
                'is_sellable' => false,
                'is_service' => false,
                'allow_negative_stock' => false,
                'min_stock' => '200.0000',
                'average_cost' => '1.5000',
                'last_cost' => '1.5000',
                'sale_price' => '0.00',
                'is_active' => true,
            ]
        );

        $itemFinished = Item::query()->firstOrCreate(
            ['sku' => 'PA-RED-50', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Redutor de Velocidade Industrial RV-50 (1:20)',
                'description' => 'Redutor de coroa e rosca sem-fim para esteiras industriais pesadas',
                'unit_of_measure_id' => $uomUn->id,
                'default_warehouse_id' => $warehouseFinished->id,
                'is_raw_material' => false,
                'is_manufactured' => true,
                'is_sellable' => true,
                'is_service' => false,
                'allow_negative_stock' => false,
                'min_stock' => '5.0000',
                'average_cost' => '369.0000',
                'last_cost' => '369.0000',
                'sale_price' => '516.60',
                'is_active' => true,
            ]
        );

        // 6. Kardex / Estoque: Entrada Inicial de Compras de Matéria-Prima
        /** @var KardexLedgerService $kardexService */
        $kardexService = app(KardexLedgerService::class);

        $existingMovementsCount = DB::table('kardex_movements')->where('tenant_id', $tenantAlfa->id)->count();
        if ($existingMovementsCount === 0) {
            // Entrada de 500 KG de Aço 1020 @ R$ 12.00
            $kardexService->record(new StockMovementDTO(
                warehouseId: $warehouseRaw->id,
                itemId: $itemBar1020->id,
                movementType: KardexMovementType::InPurchase,
                quantity: '500.0000',
                unitCost: '12.0000',
                referenceType: 'purchase_orders',
                referenceId: null,
                documentNumber: 'NF-GERDAU-9901',
                justification: 'Entrada de matéria-prima conforme NF 9901 da Gerdau',
                userId: $userAlfa->id,
            ));

            // Entrada de 200 M2 de Chapa Inox @ R$ 35.00
            $kardexService->record(new StockMovementDTO(
                warehouseId: $warehouseRaw->id,
                itemId: $itemChapaInox->id,
                movementType: KardexMovementType::InPurchase,
                quantity: '200.0000',
                unitCost: '35.0000',
                referenceType: 'purchase_orders',
                referenceId: null,
                documentNumber: 'NF-GERDAU-9901',
                justification: 'Entrada de matéria-prima conforme NF 9901 da Gerdau',
                userId: $userAlfa->id,
            ));

            // Entrada de 1.000 UN de Parafusos M10 @ R$ 1.50
            $kardexService->record(new StockMovementDTO(
                warehouseId: $warehouseRaw->id,
                itemId: $itemParafuso->id,
                movementType: KardexMovementType::InPurchase,
                quantity: '1000.0000',
                unitCost: '1.5000',
                referenceType: 'purchase_orders',
                referenceId: null,
                documentNumber: 'NF-FIX-1044',
                justification: 'Entrada de parafusos conforme NF 1044',
                userId: $userAlfa->id,
            ));
        }

        // 7. Engenharia: Centros de Trabalho e Roteiros Operacionais
        $wcCnc = WorkCenter::query()->firstOrCreate(
            ['code' => 'WC-CNC-01', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Torno CNC Mazak Quick Turn 250',
                'description' => 'Usinagem de precisão para eixos, buchas e engrenagens',
                'hourly_cost' => '85.0000',
                'capacity_per_day_hours' => '16.00',
                'is_active' => true,
            ]
        );

        $wcSolda = WorkCenter::query()->firstOrCreate(
            ['code' => 'WC-SLD-01', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Célula de Caldeiraria & Solda Robotizada TIG/MIG',
                'description' => 'Montagem, ponteamento e solda de carcaças estruturais',
                'hourly_cost' => '70.0000',
                'capacity_per_day_hours' => '16.00',
                'is_active' => true,
            ]
        );

        $wcMontagem = WorkCenter::query()->firstOrCreate(
            ['code' => 'WC-MNT-01', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Bancada de Montagem, Ajustagem e Testes Hidráulicos',
                'description' => 'Montagem mecânica dos redutores, abastecimento de óleo e ensaio sob carga',
                'hourly_cost' => '60.0000',
                'capacity_per_day_hours' => '8.00',
                'is_active' => true,
            ]
        );

        // Ficha Técnica (BOM) do Redutor RV-50
        /** @var Bom $bom */
        $bom = Bom::query()->firstOrCreate(
            ['code' => 'BOM-RED-50-V1', 'tenant_id' => $tenantAlfa->id],
            [
                'item_id' => $itemFinished->id,
                'version' => 1,
                'description' => 'Ficha Técnica Padrão do Redutor RV-50',
                'batch_size' => '1.0000',
                'total_estimated_cost' => '369.0000',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        // Insumos da BOM (por unidade de produto acabado)
        // 12 KG de Aço 1020 (R$ 144,00)
        BomItem::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id, 'bom_id' => $bom->id, 'item_id' => $itemBar1020->id],
            [
                'unit_of_measure_id' => $uomKg->id,
                'quantity' => '12.0000',
                'scrap_rate_percentage' => '5.00',
                'unit_cost' => '12.0000',
                'total_cost' => '144.0000',
                'notes' => 'Usinagem do eixo de entrada e coroa',
            ]
        );

        // 1.5 M2 de Chapa Inox (R$ 52,50)
        BomItem::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id, 'bom_id' => $bom->id, 'item_id' => $itemChapaInox->id],
            [
                'unit_of_measure_id' => $uomM2->id,
                'quantity' => '1.5000',
                'scrap_rate_percentage' => '0.00',
                'unit_cost' => '35.0000',
                'total_cost' => '52.5000',
                'notes' => 'Corte a laser e caldeiraria da carcaça do redutor',
            ]
        );

        // 15 UN de Parafusos M10 (R$ 22,50)
        BomItem::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id, 'bom_id' => $bom->id, 'item_id' => $itemParafuso->id],
            [
                'unit_of_measure_id' => $uomUn->id,
                'quantity' => '15.0000',
                'scrap_rate_percentage' => '0.00',
                'unit_cost' => '1.5000',
                'total_cost' => '22.5000',
                'notes' => 'Fechamento da tampa e flanges de acoplamento',
            ]
        );

        // Roteiro Operacional da BOM
        RoutingStep::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id, 'bom_id' => $bom->id, 'sequence' => 10],
            [
                'work_center_id' => $wcCnc->id,
                'name' => 'Torneamento e Fresamento CNC dos Eixos',
                'description' => 'Desbaste, acabamento e abertura de rasgos de chaveta',
                'setup_time_minutes' => '30.00',
                'run_time_minutes_per_unit' => '45.00',
            ]
        );

        RoutingStep::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id, 'bom_id' => $bom->id, 'sequence' => 20],
            [
                'work_center_id' => $wcSolda->id,
                'name' => 'Caldeiraria, Conformação e Soldagem da Carcaça',
                'description' => 'Dobra em prensa CNC e cordão contínuo de solda TIG',
                'setup_time_minutes' => '15.00',
                'run_time_minutes_per_unit' => '30.00',
            ]
        );

        RoutingStep::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id, 'bom_id' => $bom->id, 'sequence' => 30],
            [
                'work_center_id' => $wcMontagem->id,
                'name' => 'Montagem, Ajuste de Folgas e Teste em Vazio',
                'description' => 'Encaixe de rolamentos, coroa, vedação com retentores e teste de ruído',
                'setup_time_minutes' => '10.00',
                'run_time_minutes_per_unit' => '40.00',
            ]
        );

        // 8. CRM & Orçamentos: Orçamento Técnico e Conversão em Pedido & OP
        /** @var Quote $quote */
        $quote = Quote::query()->firstOrCreate(
            ['quote_number' => 'ORC-2026-001', 'tenant_id' => $tenantAlfa->id],
            [
                'partner_id' => $customer->id,
                'deal_id' => $dealWon->id,
                'status' => QuoteStatus::Approved,
                'total_materials_cost' => '2190.0000', // 10 un * 219.00
                'total_operations_cost' => '1500.0000', // 10 un * 150.00
                'markup_percentage' => '40.00',
                'margin_percentage' => '28.57',
                'final_total' => '5166.00',
                'lead_time_days' => 15,
                'valid_until' => Carbon::now()->addDays(30),
                'approved_at' => Carbon::now()->subDays(5),
                'notes' => 'Orçamento técnico para lote de 10 redutores industriais RV-50.',
            ]
        );

        QuoteItem::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id, 'quote_id' => $quote->id, 'item_id' => $itemFinished->id],
            [
                'bom_id' => $bom->id,
                'quantity' => '10.0000',
                'unit_cost' => '369.0000',
                'markup_percentage' => '40.00',
                'unit_price' => '516.6000',
                'total_price' => '5166.00',
                'lead_time_days' => 15,
                'notes' => 'Lote com pintura eletrostática especial cinza munsell N6,5.',
            ]
        );

        // Conversão 1-Clique do Orçamento em Pedido de Venda e Ordem de Produção
        /** @var ConvertQuoteToSalesOrderAction $convertAction */
        $convertAction = app(ConvertQuoteToSalesOrderAction::class);
        if ($quote->status !== QuoteStatus::Converted) {
            $salesOrder = $convertAction->execute($quote);
        } else {
            $salesOrder = $quote->salesOrder;
        }

        // 9. Produção: Apontamento Real com Baixa no Kardex
        /** @var ProductionOrder|null $productionOrder */
        $productionOrder = ProductionOrder::query()
            ->where('tenant_id', $tenantAlfa->id)
            ->where('item_id', $itemFinished->id)
            ->first();

        if ($productionOrder && bccomp((string) $productionOrder->produced_quantity, '0.0000', 4) === 0) {
            /** @var ProductionOrderService $poService */
            $poService = app(ProductionOrderService::class);

            // Aponta 5 unidades produzidas (50% do lote planejado de 10)
            $poService->recordProduction(new RecordProductionDTO(
                productionOrderId: $productionOrder->id,
                producedQuantity: '5.0000',
                scrappedQuantity: '0.0000',
                notes: 'Apontamento do 1º turno: Usinagem e montagem de 5 unidades aprovadas pelo CQ.',
                userId: $userAlfa->id,
            ));
        }

        // 10. Módulo Financeiro: Contas Bancárias, Plano de Contas e Centros de Custo
        /** @var FinancialAccount $bankItau */
        $bankItau = FinancialAccount::query()->firstOrCreate(
            ['name' => 'Banco Itaú Empresas C/C', 'tenant_id' => $tenantAlfa->id],
            [
                'type' => AccountType::Checking,
                'bank_name' => 'Banco Itaú Unibanco S/A',
                'agency_number' => '0450',
                'account_number' => '12345-6',
                'current_balance' => '45000.00',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        FinancialAccount::query()->firstOrCreate(
            ['name' => 'Banco Bradesco Cobrança', 'tenant_id' => $tenantAlfa->id],
            [
                'type' => AccountType::Checking,
                'bank_name' => 'Banco Bradesco S/A',
                'agency_number' => '1200',
                'account_number' => '98765-4',
                'current_balance' => '18500.00',
                'is_active' => true,
                'is_default' => false,
            ]
        );

        FinancialAccount::query()->firstOrCreate(
            ['name' => 'Caixa Físico Fabril', 'tenant_id' => $tenantAlfa->id],
            [
                'type' => AccountType::Cash,
                'bank_name' => null,
                'agency_number' => null,
                'account_number' => null,
                'current_balance' => '1200.00',
                'is_active' => true,
                'is_default' => false,
            ]
        );

        // Plano de Contas Gerencial
        /** @var ChartOfAccount $accountRevenue */
        $accountRevenue = ChartOfAccount::query()->firstOrCreate(
            ['code' => '3.1.01', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Receita Operacional Bruta - Venda de Produtos Industriais',
                'type' => AccountCategoryType::Revenue,
                'is_active' => true,
            ]
        );

        /** @var ChartOfAccount $accountCostRaw */
        $accountCostRaw = ChartOfAccount::query()->firstOrCreate(
            ['code' => '4.1.01', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Custos com Aquisição de Matérias-Primas e Aços',
                'type' => AccountCategoryType::Expense,
                'is_active' => true,
            ]
        );

        ChartOfAccount::query()->firstOrCreate(
            ['code' => '4.2.01', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Despesas Operacionais e Manutenção Preventiva de Máquinas',
                'type' => AccountCategoryType::Expense,
                'is_active' => true,
            ]
        );

        // Centros de Custo
        /** @var CostCenter $costCenterUsin */
        $costCenterUsin = CostCenter::query()->firstOrCreate(
            ['code' => 'CC-USIN', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Centro de Usinagem & Torneamento CNC',
                'description' => 'Operações no parque de usinagem pesada',
                'is_active' => true,
            ]
        );

        CostCenter::query()->firstOrCreate(
            ['code' => 'CC-CALD', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Centro de Caldeiraria & Estruturas Metálicas',
                'description' => 'Corte, dobra e soldagem de componentes',
                'is_active' => true,
            ]
        );

        CostCenter::query()->firstOrCreate(
            ['code' => 'CC-ADM', 'tenant_id' => $tenantAlfa->id],
            [
                'name' => 'Administrativo, Comercial e Engenharia',
                'description' => 'Custos fixos de backoffice e vendas',
                'is_active' => true,
            ]
        );

        // 11. Financeiro: Lançamentos de Contas a Pagar e Contas a Receber
        /** @var FinancialEntryService $finEntryService */
        $finEntryService = app(FinancialEntryService::class);
        /** @var SettleFinancialPaymentAction $settleAction */
        $settleAction = app(SettleFinancialPaymentAction::class);

        // Contas a Pagar: Compra Matéria-Prima Gerdau (NF-GERDAU-9901 - R$ 6.000,00)
        $payableGerdau = FinancialEntry::query()->where('document_number', 'NF-GERDAU-9901')->first();
        if (! $payableGerdau) {
            $createdPayables = $finEntryService->createWithInstallments(new CreateFinancialEntryDTO(
                entryType: EntryType::Payable,
                chartOfAccountId: $accountCostRaw->id,
                documentNumber: 'NF-GERDAU-9901',
                title: 'Aquisição de 500kg Aço 1020 - Siderúrgica Gerdau',
                totalAmount: '6000.00',
                firstDueDate: Carbon::now()->addDays(20)->toDateString(),
                partnerId: $supplier->id,
                costCenterId: $costCenterUsin->id,
                salesOrderId: null,
                installmentsCount: 1,
                issueDate: Carbon::now()->subDays(5)->toDateString(),
                competenceDate: Carbon::now()->subDays(5)->toDateString(),
                notes: 'Compra a prazo com pagamento de 50% de entrada antecipada.',
            ));
            $payableGerdau = $createdPayables->first();

            // Baixa parcial de R$ 3.000,00 da entrada antecipada via Itaú
            if ($payableGerdau) {
                $settleAction->execute(new SettleFinancialPaymentDTO(
                    financialEntryId: $payableGerdau->id,
                    financialAccountId: $bankItau->id,
                    paymentMethod: PaymentMethod::BankTransfer,
                    paymentDate: Carbon::now()->subDays(3)->toDateString(),
                    amount: '3000.00',
                    interestPaid: '0.00',
                    finePaid: '0.00',
                    discountApplied: '0.00',
                    notes: 'Pagamento antecipado de 50% da NF Gerdau 9901.',
                ));
            }
        }

        // Contas a Receber: Venda dos 10 Redutores (R$ 5.166,00 parcelado em 2x)
        $existingReceivables = FinancialEntry::query()->where('sales_order_id', $salesOrder->id)->get();
        if ($existingReceivables->isEmpty()) {
            $receivables = $finEntryService->createWithInstallments(new CreateFinancialEntryDTO(
                entryType: EntryType::Receivable,
                chartOfAccountId: $accountRevenue->id,
                documentNumber: $salesOrder->order_number,
                title: "Venda 10 Redutores RV-50 ({$salesOrder->order_number})",
                totalAmount: (string) $salesOrder->total_amount,
                firstDueDate: Carbon::now()->toDateString(),
                partnerId: $customer->id,
                costCenterId: $costCenterUsin->id,
                salesOrderId: $salesOrder->id,
                installmentsCount: 2,
                issueDate: Carbon::now()->subDays(2)->toDateString(),
                competenceDate: Carbon::now()->subDays(2)->toDateString(),
                intervalDays: 30,
                notes: 'Faturamento faturado em 2 parcelas (Entrada à vista via PIX + 30 dias boleto).',
            ));

            // Baixa da 1ª Parcela (PIX à vista no Itaú)
            /** @var FinancialEntry $installment1 */
            $installment1 = $receivables->first();
            if ($installment1) {
                $settleAction->execute(new SettleFinancialPaymentDTO(
                    financialEntryId: $installment1->id,
                    financialAccountId: $bankItau->id,
                    paymentMethod: PaymentMethod::Pix,
                    paymentDate: Carbon::now()->toDateString(),
                    amount: (string) $installment1->balance_amount,
                    notes: 'Confirmação automática de recebimento via PIX Gateway.',
                ));
            }
        } else {
            /** @var FinancialEntry $installment1 */
            $installment1 = $existingReceivables->first();
        }

        // 12. Integrações Fiscais & Gateways de Pagamento
        /** @var VaultEncryption $vault */
        $vault = app(VaultEncryption::class);

        /** @var TenantTaxSetting $taxSetting */
        $taxSetting = TenantTaxSetting::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id],
            [
                'environment' => FiscalEnvironment::Homologation,
                'tax_regime' => TaxRegime::SimplesNacional,
                'state_registration' => '254.890.123.456',
                'municipal_registration' => '889900',
                'nfe_series' => 1,
                'next_nfe_number' => 101,
                'default_cfop' => '5101',
                'default_csosn' => '102',
                'certificate_path' => 'certificates/tenant-alfa-a1.pfx',
                'certificate_password' => $vault->encrypt('AlfaCertPass2026@Security', $tenantAlfa->id, 'fiscal_cert'),
                'certificate_expires_at' => Carbon::now()->addYear(),
            ]
        );

        // Gateway de Pagamento Asaas (Padrão)
        $encryptedAsaasKey = $vault->encrypt('asaas_live_mock_api_key_alfa_998877665544', $tenantAlfa->id, 'gateway_api_key');
        $encryptedAsaasSecret = $vault->encrypt('whsec_asaas_demo_signature_secret_123', $tenantAlfa->id, 'gateway_webhook_secret');

        PaymentGatewaySetting::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id, 'gateway' => PaymentGateway::Asaas],
            [
                'api_key' => $encryptedAsaasKey,
                'webhook_secret' => $encryptedAsaasSecret,
                'is_sandbox' => true,
                'is_active' => true,
                'is_default' => true,
            ]
        );

        // Gateway de Pagamento Stripe (Secundário)
        $encryptedStripeKey = $vault->encrypt('sk_test_stripe_mock_key_alfa_112233445566', $tenantAlfa->id, 'gateway_api_key');
        $encryptedStripeSecret = $vault->encrypt('whsec_stripe_mock_signature_secret_998', $tenantAlfa->id, 'gateway_webhook_secret');

        PaymentGatewaySetting::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id, 'gateway' => PaymentGateway::Stripe],
            [
                'api_key' => $encryptedStripeKey,
                'webhook_secret' => $encryptedStripeSecret,
                'is_sandbox' => true,
                'is_active' => true,
                'is_default' => false,
            ]
        );

        // Emissão e Autorização de NF-e nº 100 para o Pedido de Venda
        $nfe = FiscalInvoice::query()->firstOrCreate(
            ['tenant_id' => $tenantAlfa->id, 'sales_order_id' => $salesOrder->id],
            [
                'partner_id' => $customer->id,
                'invoice_type' => FiscalInvoiceType::Nfe,
                'invoice_number' => 100,
                'series' => 1,
                'environment' => FiscalEnvironment::Homologation,
                'status' => FiscalInvoiceStatus::Draft,
                'total_amount' => (string) $salesOrder->total_amount,
                'tax_amount' => '310.00',
                'payload' => [
                    'items_count' => 1,
                    'cfop' => '5101',
                    'natureza_operacao' => 'Venda de produção do estabelecimento',
                ],
                'issued_at' => Carbon::now()->subDays(1),
            ]
        );

        if ($nfe->status !== FiscalInvoiceStatus::Authorized) {
            /** @var FiscalEmitterService $fiscalService */
            $fiscalService = app(FiscalEmitterService::class);
            $fiscalService->emitNfe($nfe);
        }

        // Registro de Webhook Idempotente Mockado
        if ($installment1) {
            WebhookEvent::query()->firstOrCreate(
                ['gateway' => PaymentGateway::Asaas, 'external_event_id' => 'evt_asaas_demo_pix_99001'],
                [
                    'tenant_id' => $tenantAlfa->id,
                    'event_type' => 'PAYMENT_RECEIVED',
                    'financial_entry_id' => $installment1->id,
                    'payload' => [
                        'id' => 'evt_asaas_demo_pix_99001',
                        'event' => 'PAYMENT_RECEIVED',
                        'payment' => [
                            'id' => 'pay_asaas_demo_01',
                            'value' => (string) $installment1->original_amount,
                            'netValue' => (string) $installment1->original_amount,
                            'externalReference' => $installment1->id,
                            'billingType' => 'PIX',
                            'clientPaymentDate' => Carbon::now()->toDateString(),
                        ],
                    ],
                    'status' => WebhookEventStatus::Processed,
                    'processed_at' => Carbon::now()->subHours(2),
                ]
            );
        }

        // =========================================================================
        // SEEDING TENANT BETA (Indústria Beta S/A - Baseline de Isolamento)
        // =========================================================================
        $this->setTenantContext($tenantBeta);

        $uomKgBeta = UnitOfMeasure::query()->where('code', 'KG')->firstOrFail();

        // Almoxarifado Beta
        $warehouseBeta = Warehouse::query()->firstOrCreate(
            ['code' => 'ALM-BETA-01', 'tenant_id' => $tenantBeta->id],
            [
                'name' => 'Depósito Matriz Beta',
                'description' => 'Depósito fabril da Indústria Beta S/A',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        // Cliente Exclusivo de Beta
        Partner::query()->firstOrCreate(
            ['document_number' => '99.888.777/0001-55', 'tenant_id' => $tenantBeta->id],
            [
                'partner_type' => PartnerType::Customer,
                'person_type' => PersonType::LegalEntity,
                'name' => 'Beta Cliente Automotivo Ltda',
                'trade_name' => 'Cliente Exclusivo Beta',
                'email' => 'compras@betaautomotivo.com.br',
                'phone' => '(41) 3333-4444',
                'is_active' => true,
            ]
        );

        // Item Exclusivo de Beta
        Item::query()->firstOrCreate(
            ['sku' => 'MP-BETA-ALUM', 'tenant_id' => $tenantBeta->id],
            [
                'name' => 'Barra Alumínio Estrutural 6061-T6',
                'description' => 'Insumo aeronáutico e automotivo leve',
                'unit_of_measure_id' => $uomKgBeta->id,
                'default_warehouse_id' => $warehouseBeta->id,
                'is_raw_material' => true,
                'is_manufactured' => false,
                'is_sellable' => false,
                'is_service' => false,
                'allow_negative_stock' => false,
                'min_stock' => '10.0000',
                'average_cost' => '45.0000',
                'last_cost' => '45.0000',
                'sale_price' => '0.00',
                'is_active' => true,
            ]
        );

        // Conta Bancária Exclusiva de Beta
        FinancialAccount::query()->firstOrCreate(
            ['name' => 'Banco Santander Beta C/C', 'tenant_id' => $tenantBeta->id],
            [
                'type' => AccountType::Checking,
                'bank_name' => 'Banco Santander Brasil S/A',
                'agency_number' => '3344',
                'account_number' => '55667-8',
                'current_balance' => '10000.00',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        // Limpa contexto de tenant
        app(TenantContext::class)->clear();
        setPermissionsTeamId(null);
    }

    private function setTenantContext(Tenant $tenant): void
    {
        app(TenantContext::class)->set($tenant);
        setPermissionsTeamId($tenant->id);

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("SELECT set_config('app.current_tenant_id', ?, false)", [$tenant->id]);
        }
    }
}
