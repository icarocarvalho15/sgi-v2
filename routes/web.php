<?php

declare(strict_types=1);

use App\Core\Tenancy\Middleware\SetTenantContext;
use App\Modules\CRM\Controllers\CrmKanbanController;
use App\Modules\CRM\Controllers\PartnerController;
use App\Modules\Quotes\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([SetTenantContext::class])->group(function () {
    // Módulo CRM: Clientes e Fornecedores
    Route::resource('crm/partners', PartnerController::class)->names('crm.partners');

    // Módulo CRM: Funil de Vendas (Kanban)
    Route::get('crm/kanban', [CrmKanbanController::class, 'index'])->name('crm.kanban.index');
    Route::post('crm/deals/{deal}/stage', [CrmKanbanController::class, 'updateStage'])->name('crm.deals.stage');

    // Módulo Orçamentos Técnicos (Gráficas e Brindes)
    Route::post('quotes/calculate', [QuoteController::class, 'calculate'])->name('quotes.calculate');
    Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');
    Route::resource('quotes', QuoteController::class);

    // Módulo Engenharia & Chão de Fábrica (PCP Gráfico)
    Route::resource('production/boms', \App\Modules\Production\Controllers\BomController::class)->names('production.boms');
    Route::resource('production/orders', \App\Modules\Production\Controllers\ProductionOrderController::class)->names('production.orders');
    Route::post('production/orders/{order}/status', [\App\Modules\Production\Controllers\ProductionOrderController::class, 'updateStatus'])->name('production.orders.status');
    Route::post('production/orders/{order}/log-time', [\App\Modules\Production\Controllers\ProductionOrderController::class, 'logTime'])->name('production.orders.log-time');
});
