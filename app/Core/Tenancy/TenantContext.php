<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use App\Core\Tenancy\Models\Tenant;
use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

final class TenantContext
{
    private ?Tenant $currentTenant = null;

    private bool $isLandlordBypass = false;

    /**
     * Define o tenant ativo e sincroniza com PostgreSQL e Spatie Permission.
     */
    public function set(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($tenant->id);
        }

        $this->setDatabaseSessionConfig($tenant->id);
    }

    /**
     * Define o tenant ativo a partir do UUID.
     */
    public function setById(string $tenantId): void
    {
        $tenant = Tenant::query()->find($tenantId);

        if ($tenant instanceof Tenant) {
            $this->set($tenant);

            return;
        }

        // Caso a model de tenant ainda não tenha sido hidratada (ex: workers de fila leves)
        $ephemeralTenant = new Tenant(['id' => $tenantId]);
        $ephemeralTenant->id = $tenantId;
        $this->currentTenant = $ephemeralTenant;

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($tenantId);
        }

        $this->setDatabaseSessionConfig($tenantId);
    }

    /**
     * Retorna o tenant atualmente ativo.
     */
    public function get(): ?Tenant
    {
        return $this->currentTenant;
    }

    /**
     * Retorna o UUID do tenant ativo ou nulo.
     */
    public function id(): ?string
    {
        return $this->currentTenant?->id;
    }

    /**
     * Verifica se existe um tenant configurado no contexto.
     */
    public function has(): bool
    {
        return $this->currentTenant !== null;
    }

    /**
     * Limpa o contexto de tenant da sessão atual e reseta o PostgreSQL e Spatie Permission.
     */
    public function clear(): void
    {
        $this->currentTenant = null;

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId(null);
        }

        $this->resetDatabaseSessionConfig();
    }

    /**
     * Executa uma operação como Landlord (Bypass de Escopo de Tenant) com garantia de limpeza.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     *
     * @throws Throwable
     */
    public function bypass(Closure $callback): mixed
    {
        $previousBypassState = $this->isLandlordBypass;
        $this->isLandlordBypass = true;

        try {
            return $callback();
        } finally {
            $this->isLandlordBypass = $previousBypassState;
        }
    }

    /**
     * Retorna se a sessão está em modo de bypass Landlord.
     */
    public function isLandlordBypass(): bool
    {
        return $this->isLandlordBypass;
    }

    /**
     * Injeta a variável de sessão app.current_tenant_id no PostgreSQL.
     */
    private function setDatabaseSessionConfig(string $tenantId): void
    {
        try {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement("SELECT set_config('app.current_tenant_id', ?, false)", [$tenantId]);
            }
        } catch (Throwable) {
            // Em ambientes sem conexão de banco inicializada ainda, ignora graceful
        }
    }

    /**
     * Reseta a variável de sessão app.current_tenant_id no PostgreSQL.
     */
    private function resetDatabaseSessionConfig(): void
    {
        try {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement("SELECT set_config('app.current_tenant_id', '', false)");
            }
        } catch (Throwable) {
            // Em ambientes sem conexão de banco inicializada ainda, ignora graceful
        }
    }
}
