<?php

declare(strict_types=1);

use App\Modules\CentralAdmin\Http\Controllers\DashboardController as AdminDashboardController;
use App\Modules\CentralAdmin\Http\Controllers\ImpersonationController;
use App\Modules\CentralAdmin\Http\Controllers\PlanController;
use App\Modules\CentralAdmin\Http\Controllers\TenantController;
use App\Modules\CRM\Http\Controllers\CrmDealController;
use App\Modules\CRM\Http\Controllers\PartnerController;
use App\Modules\CRM\Http\Controllers\QuoteController;
use App\Modules\CRM\Http\Controllers\SalesOrderController;
use App\Modules\Finance\Http\Controllers\ChartOfAccountController;
use App\Modules\Finance\Http\Controllers\CostCenterController;
use App\Modules\Finance\Http\Controllers\FinancialAccountController;
use App\Modules\Finance\Http\Controllers\FinancialEntryController;
use App\Modules\Finance\Http\Controllers\FinancialPaymentController;
use App\Modules\Finance\Http\Controllers\FinancialReportController;
use App\Modules\Fiscal\Http\Controllers\FiscalInvoiceController;
use App\Modules\Fiscal\Http\Controllers\PaymentGatewayController;
use App\Modules\Fiscal\Http\Controllers\TaxSettingController;
use App\Modules\Fiscal\Http\Controllers\WebhookController;
use App\Modules\IdentityACL\Http\Controllers\AuthController;
use App\Modules\IdentityACL\Http\Controllers\TenantSelectionController;
use App\Modules\Inventory\Http\Controllers\InventoryBalanceController;
use App\Modules\Inventory\Http\Controllers\ItemController;
use App\Modules\Inventory\Http\Controllers\KardexMovementController;
use App\Modules\Inventory\Http\Controllers\StockAdjustmentController;
use App\Modules\Inventory\Http\Controllers\WarehouseController;
use App\Modules\Production\Http\Controllers\BomController;
use App\Modules\Production\Http\Controllers\ProductionOrderController;
use App\Modules\Production\Http\Controllers\ShopFloorController;
use App\Modules\Production\Http\Controllers\WorkCenterController;
use App\Modules\Tenant\Http\Controllers\DashboardController as TenantDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// 1. Rota Pública Inicial
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// 2. Autenticação Global (SaaS Landlord & Tenants)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// 3. Seletor de Tenant (Usuários com múltiplos tenants)
Route::middleware('auth')->group(function () {
    Route::get('/select-tenant', [TenantSelectionController::class, 'index'])->name('identity.select-tenant');
    Route::post('/select-tenant/{tenant}', [TenantSelectionController::class, 'select'])->name('identity.select-tenant.submit');
});

// 4. Core Admin (Landlord) - Gestão Global do SaaS
Route::middleware(['auth', 'super.admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        // Gestão de Clientes / Tenants
        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::patch('/tenants/{tenant}/status', [TenantController::class, 'updateStatus'])->name('tenants.status');

        // Gestão de Planos
        Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
        Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
        Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');

        // Impersonation Auditado (Rota estática leave deve preceder o parâmetro dinâmico {tenant})
        Route::post('/impersonate/leave', [ImpersonationController::class, 'stop'])->name('impersonate.stop');
        Route::post('/impersonate/{tenant}', [ImpersonationController::class, 'start'])->name('impersonate.start');
    });

// 5. Tenant App (ERP/CRM Operacional da Indústria)
Route::middleware(['auth', 'tenant.context', 'tenant.active'])
    ->prefix('app')
    ->name('tenant.')
    ->group(function () {
        Route::get('/', TenantDashboardController::class)->name('dashboard');

        // Módulo de Suprimentos, Produtos & Kardex
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/', fn () => redirect()->route('tenant.inventory.items.index'));

            // Catálogo de Itens / Produtos
            Route::get('/items', [ItemController::class, 'index'])->name('items.index');
            Route::post('/items', [ItemController::class, 'store'])->name('items.store');
            Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');

            // Almoxarifados / Depósitos
            Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
            Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');

            // Saldos em Estoque
            Route::get('/balances', [InventoryBalanceController::class, 'index'])->name('balances.index');

            // Razão Imutável (Extrato Kardex)
            Route::get('/kardex', [KardexMovementController::class, 'index'])->name('kardex.index');

            // Ajuste Manual de Estoque (com Justificativa Obrigatória)
            Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
        });

        // Módulo de Engenharia & Produção (Chão de Fábrica)
        Route::prefix('production')->name('production.')->group(function () {
            Route::get('/', fn () => redirect()->route('tenant.production.orders.index'));

            // Centros de Trabalho
            Route::get('/work-centers', [WorkCenterController::class, 'index'])->name('work-centers.index');
            Route::post('/work-centers', [WorkCenterController::class, 'store'])->name('work-centers.store');
            Route::put('/work-centers/{workCenter}', [WorkCenterController::class, 'update'])->name('work-centers.update');

            // Fichas Técnicas (BOMs)
            Route::get('/boms', [BomController::class, 'index'])->name('boms.index');
            Route::post('/boms', [BomController::class, 'store'])->name('boms.store');
            Route::post('/boms/{bom}/rollup', [BomController::class, 'calculateRollup'])->name('boms.rollup');

            // Ordens de Produção (OPs)
            Route::get('/orders', [ProductionOrderController::class, 'index'])->name('orders.index');
            Route::post('/orders', [ProductionOrderController::class, 'store'])->name('orders.store');
            Route::get('/orders/{order}', [ProductionOrderController::class, 'show'])->name('orders.show');
            Route::post('/orders/{order}/release', [ProductionOrderController::class, 'release'])->name('orders.release');
            Route::post('/orders/{order}/record-production', [ProductionOrderController::class, 'recordProduction'])->name('orders.record-production');

            // PWA / Chão de Fábrica Mobile
            Route::get('/shop-floor', [ShopFloorController::class, 'index'])->name('shop-floor.index');
            Route::post('/operations/{operation}/start', [ShopFloorController::class, 'start'])->name('operations.start');
            Route::post('/operations/{operation}/pause', [ShopFloorController::class, 'pause'])->name('operations.pause');
            Route::post('/operations/{operation}/complete', [ShopFloorController::class, 'complete'])->name('operations.complete');
        });

        // Módulo de CRM (Clientes, Fornecedores & Funil de Vendas)
        Route::prefix('crm')->name('crm.')->group(function () {
            Route::get('/', fn () => redirect()->route('tenant.crm.deals.index'));

            // Parceiros Comerciais
            Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
            Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
            Route::put('/partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');

            // Funil de Vendas & Oportunidades (Kanban)
            Route::get('/deals', [CrmDealController::class, 'index'])->name('deals.index');
            Route::post('/deals', [CrmDealController::class, 'store'])->name('deals.store');
            Route::patch('/deals/{deal}/stage', [CrmDealController::class, 'updateStage'])->name('deals.stage');
        });

        // Módulo de Vendas & Orçamentos Sob Encomenda
        Route::prefix('sales')->name('sales.')->group(function () {
            Route::get('/', fn () => redirect()->route('tenant.sales.quotes.index'));

            // Orçamentos Técnicos
            Route::get('/quotes', [QuoteController::class, 'index'])->name('quotes.index');
            Route::post('/quotes', [QuoteController::class, 'store'])->name('quotes.store');
            Route::get('/quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
            Route::post('/quotes/calculate', [QuoteController::class, 'calculate'])->name('quotes.calculate');
            Route::post('/quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');

            // Pedidos de Venda
            Route::get('/orders', [SalesOrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [SalesOrderController::class, 'show'])->name('orders.show');
        });

        // Módulo Financeiro (Contas a Pagar/Receber, Bancos, Fluxo de Caixa & DRE)
        Route::prefix('finance')->name('finance.')->group(function () {
            Route::get('/', fn () => redirect()->route('tenant.finance.entries.index'));

            // Títulos Financeiros (Contas a Pagar e Receber)
            Route::get('/entries', [FinancialEntryController::class, 'index'])->name('entries.index');
            Route::post('/entries', [FinancialEntryController::class, 'store'])->name('entries.store');
            Route::get('/entries/{entry}', [FinancialEntryController::class, 'show'])->name('entries.show');

            // Liquidação e Estorno
            Route::post('/entries/{entry}/settle', [FinancialPaymentController::class, 'settle'])->name('payments.settle');
            Route::post('/payments/{payment}/reverse', [FinancialPaymentController::class, 'reverse'])->name('payments.reverse');

            // Contas Bancárias / Caixa, Plano de Contas & Centros de Custo
            Route::get('/accounts', [FinancialAccountController::class, 'index'])->name('accounts.index');
            Route::post('/accounts', [FinancialAccountController::class, 'store'])->name('accounts.store');
            Route::post('/chart-of-accounts', [ChartOfAccountController::class, 'store'])->name('chart-of-accounts.store');
            Route::post('/cost-centers', [CostCenterController::class, 'store'])->name('cost-centers.store');

            // Relatórios Gerenciais (Fluxo de Caixa & DRE)
            Route::get('/reports/cash-flow', [FinancialReportController::class, 'cashFlow'])->name('reports.cash-flow');
            Route::get('/reports/dre', [FinancialReportController::class, 'dre'])->name('reports.dre');
        });

        // Módulo Fiscal & Gateways (NFe, NFSe, Parâmetros Fiscais & Pagamentos)
        Route::prefix('fiscal')->name('fiscal.')->group(function () {
            Route::get('/', fn () => redirect()->route('tenant.fiscal.invoices.index'));

            // Notas Fiscais Eletrônicas
            Route::get('/invoices', [FiscalInvoiceController::class, 'index'])->name('invoices.index');
            Route::post('/invoices', [FiscalInvoiceController::class, 'store'])->name('invoices.store');
            Route::get('/invoices/{invoice}', [FiscalInvoiceController::class, 'show'])->name('invoices.show');
            Route::get('/invoices/{invoice}/download-xml', [FiscalInvoiceController::class, 'downloadXml'])->name('invoices.download-xml');
            Route::get('/invoices/{invoice}/download-pdf', [FiscalInvoiceController::class, 'downloadPdf'])->name('invoices.download-pdf');

            // Configurações Fiscais e Certificado A1
            Route::get('/settings/tax', [TaxSettingController::class, 'index'])->name('settings.tax');
            Route::post('/settings/tax', [TaxSettingController::class, 'update'])->name('settings.tax.update');

            // Configurações de Gateways de Cobrança
            Route::get('/settings/gateways', [PaymentGatewayController::class, 'index'])->name('settings.gateways');
            Route::post('/settings/gateways', [PaymentGatewayController::class, 'store'])->name('settings.gateways.store');
        });
    });

// 6. Webhooks de Gateways de Pagamento (Público, validação por assinatura HMAC)
Route::post('/api/webhooks/gateway/{gateway}', [WebhookController::class, 'handle'])->name('webhooks.gateway');
