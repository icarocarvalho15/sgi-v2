<?php

declare(strict_types=1);

use App\Modules\CentralAdmin\Http\Controllers\DashboardController as AdminDashboardController;
use App\Modules\CentralAdmin\Http\Controllers\ImpersonationController;
use App\Modules\CentralAdmin\Http\Controllers\PlanController;
use App\Modules\CentralAdmin\Http\Controllers\TenantController;
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
    });
