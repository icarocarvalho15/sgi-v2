<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Security\VaultEncryption;
use App\Core\Tenancy\Database\PostgresRlsHelper;
use App\Core\Tenancy\Middleware\CheckPlanFeatureLimit;
use App\Core\Tenancy\Middleware\EnsureTenantIsActive;
use App\Core\Tenancy\Middleware\SetTenantContext;
use App\Core\Tenancy\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, static fn (): TenantContext => new TenantContext);
        $this->app->singleton(VaultEncryption::class, static fn (): VaultEncryption => new VaultEncryption);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Router $router): void
    {
        $this->registerBlueprintMacros();
        $this->registerMiddlewareAliases($router);
    }

    /**
     * Registra macros do Blueprint para automação de Row-Level Security e colunas Multi-Tenant.
     */
    private function registerBlueprintMacros(): void
    {
        Blueprint::macro('enableTenantRls', function (): void {
            /** @var Blueprint $this */
            PostgresRlsHelper::enableTenantRls($this->getTable());
        });

        Blueprint::macro('disableTenantRls', function (): void {
            /** @var Blueprint $this */
            PostgresRlsHelper::disableTenantRls($this->getTable());
        });

        Blueprint::macro('tenantCompositeKey', function (): void {
            /** @var Blueprint $this */
            $this->unique(['tenant_id', 'id'], $this->getTable().'_tenant_composite_unique');
        });
    }

    /**
     * Registra aliases de middlewares de tenancy.
     */
    private function registerMiddlewareAliases(Router $router): void
    {
        $router->aliasMiddleware('tenant.context', SetTenantContext::class);
        $router->aliasMiddleware('tenant.active', EnsureTenantIsActive::class);
        $router->aliasMiddleware('plan.feature', CheckPlanFeatureLimit::class);
    }
}
