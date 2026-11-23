<?php

declare(strict_types=1);

use App\Core\Tenancy\Middleware\SetTenantContext;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Modules\CentralAdmin\Controllers\TenantManagementController;
use App\Modules\CoreAdmin\Controllers\CompanySettingsController;
use App\Modules\CoreAdmin\Controllers\TenantStatusController;
use App\Modules\CRM\Controllers\CrmKanbanController;
use App\Modules\CRM\Controllers\PartnerController;
use App\Modules\Quotes\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

// Autenticação (Login / Logout)
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// Rota raiz: redireciona para login se deslogado, ou CRM se autenticado
Route::get('/', function () {
    return auth()->check() ? redirect('/crm/partners') : redirect()->route('login');
});

// Painel do Landlord SaaS (Gestão Global de Tenants, Planos, Assinaturas e Auditoria)
Route::middleware(['web', EnsureSuperAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.tenants.index');
    });

    // Tenants & Assinaturas
    Route::get('tenants', [TenantManagementController::class, 'index'])->name('tenants.index');
    Route::post('tenants', [TenantManagementController::class, 'store'])->name('tenants.store');
    Route::put('tenants/{tenant}/subscription', [TenantManagementController::class, 'updateSubscription'])->name('tenants.subscription');

    // Suporte com Auditoria de Personificação Técnica
    Route::post('tenants/{tenant}/impersonate', [TenantManagementController::class, 'impersonate'])->name('tenants.impersonate');
    Route::post('impersonate/stop', [TenantManagementController::class, 'stopImpersonation'])->name('impersonate.stop');

    // Planos SaaS do Landlord
    Route::resource('plans', \App\Modules\CentralAdmin\Controllers\PlanManagementController::class)->names('plans');

    // Métricas SaaS Globais (MRR, Churn, Adoção)
    Route::get('metrics', [TenantManagementController::class, 'metrics'])->name('metrics.index');

    // Auditoria & Compliance Global
    Route::get('audit-logs', [TenantManagementController::class, 'auditLogs'])->name('audit-logs.index');
});

// Rotas do Tenant (Isolamento RLS Multi-Tenant Automático e Controle RBAC por Perfil)
Route::middleware([SetTenantContext::class])->group(function () {
    // Módulo CRM: Clientes e Fornecedores (Admin, Comercial/Vendas e Financeiro)
    Route::middleware('tenant.role:admin,sales,finance')->group(function () {
        Route::resource('crm/partners', PartnerController::class)->names('crm.partners');
    });

    // Módulo Orçamentos Técnicos & Funil de Vendas (Admin e Comercial/Vendas)
    Route::middleware('tenant.role:admin,sales')->group(function () {
        Route::get('crm/kanban', fn () => redirect()->route('quotes.index', ['view' => 'kanban']))->name('crm.kanban.index');
        Route::post('crm/deals/{deal}/stage', [CrmKanbanController::class, 'updateStage'])->name('crm.deals.stage');
        Route::post('quotes/calculate', [QuoteController::class, 'calculate'])->name('quotes.calculate');
        Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');
        Route::post('quotes/{quote}/status', [QuoteController::class, 'updateStatus'])->name('quotes.status');
        Route::resource('quotes', QuoteController::class);
    });

    // Módulo Engenharia & Chão de Fábrica (PCP Gráfico: Admin e Chão de Fábrica)
    Route::middleware('tenant.role:admin,production')->group(function () {
        Route::resource('production/boms', \App\Modules\Production\Controllers\BomController::class)->names('production.boms');
        Route::resource('production/orders', \App\Modules\Production\Controllers\ProductionOrderController::class)->names('production.orders');
        Route::post('production/orders/{order}/status', [\App\Modules\Production\Controllers\ProductionOrderController::class, 'updateStatus'])->name('production.orders.status');
        Route::post('production/orders/{order}/log-time', [\App\Modules\Production\Controllers\ProductionOrderController::class, 'logTime'])->name('production.orders.log-time');
    });

    // Módulo Suprimentos & Controle de Estoque (Balances: consulta aberta para vendas/produção; Kardex/Ajustes: produção e admin)
    Route::middleware('tenant.role:admin,sales,production')->group(function () {
        Route::get('inventory/balances', [\App\Modules\Inventory\Controllers\InventoryBalanceController::class, 'index'])->name('tenant.inventory.balances.index');
    });

    Route::middleware('tenant.role:admin,production')->group(function () {
        Route::get('inventory/kardex', [\App\Modules\Inventory\Controllers\KardexController::class, 'index'])->name('tenant.inventory.kardex.index');
        Route::get('inventory/adjustments/create', [\App\Modules\Inventory\Controllers\InventoryAdjustmentController::class, 'create'])->name('tenant.inventory.adjustments.create');
        Route::post('inventory/adjustments', [\App\Modules\Inventory\Controllers\InventoryAdjustmentController::class, 'store'])->name('tenant.inventory.adjustments.store');
    });

    // Módulo Financeiro & Contas a Pagar/Receber (Admin e Financeiro)
    Route::middleware('tenant.role:admin,finance')->group(function () {
        Route::resource('finance/entries', \App\Modules\Finance\Controllers\FinancialEntryController::class)->names('tenant.finance.entries');
        Route::post('finance/payments', [\App\Modules\Finance\Controllers\FinancialPaymentController::class, 'store'])->name('tenant.finance.payments.store');
        Route::get('finance/reports', [\App\Modules\Finance\Controllers\FinancialReportController::class, 'index'])->name('tenant.finance.reports.index');

        // Salvar configurações fiscais (acessível por admin e financeiro)
        Route::post('settings/fiscal', [\App\Modules\Integrations\Controllers\FiscalSettingsController::class, 'store'])->name('tenant.settings.fiscal.store');
    });

    // Módulo Configurações Fiscais e Gateways (Acessível por Admin e Financeiro)
    Route::middleware('tenant.role:admin,finance')->group(function () {
        Route::get('settings/fiscal', [\App\Modules\Integrations\Controllers\FiscalSettingsController::class, 'index'])->name('tenant.settings.fiscal.index');
        Route::post('settings/fiscal', [\App\Modules\Integrations\Controllers\FiscalSettingsController::class, 'store'])->name('tenant.settings.fiscal.store');
    });

    // Módulo Central de Configurações da Empresa & Status (Admin / Dono)
    Route::middleware('tenant.role:admin')->group(function () {
        Route::get('settings', [CompanySettingsController::class, 'index'])->name('tenant.settings.index');
        Route::put('settings/company', [CompanySettingsController::class, 'updateCompany'])->name('tenant.settings.company.update');
        Route::post('settings/employees', [CompanySettingsController::class, 'storeEmployee'])->name('tenant.settings.employees.store');
        Route::put('settings/employees/{user}', [CompanySettingsController::class, 'updateEmployee'])->name('tenant.settings.employees.update');
        Route::delete('settings/employees/{user}', [CompanySettingsController::class, 'destroyEmployee'])->name('tenant.settings.employees.destroy');

        Route::resource('settings/statuses', TenantStatusController::class)->names('tenant.settings.statuses');
    });
});

// Receptor Global de Webhooks (Blindado com Validação HMAC e Idempotência)
Route::post('api/webhooks/{provider}', [\App\Modules\Integrations\Controllers\WebhookController::class, 'handle'])->name('webhooks.handle');
