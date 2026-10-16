<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Queue;

use App\Core\Tenancy\Exceptions\TenantContextMissingException;
use App\Core\Tenancy\TenantContext;

trait InteractsWithTenantQueue
{
    public string $tenantId;

    /**
     * Captura o tenant ativo no momento do dispatch ou permite injeção explícita.
     */
    public function bindTenantContext(?string $tenantId = null): self
    {
        if ($tenantId !== null && $tenantId !== '') {
            $this->tenantId = $tenantId;

            return $this;
        }

        $contextId = app(TenantContext::class)->id();

        if ($contextId === null || $contextId === '') {
            throw new TenantContextMissingException(
                'Não foi possível despachar o Job '.static::class.' pois não há tenant ativo no contexto.'
            );
        }

        $this->tenantId = $contextId;

        return $this;
    }

    /**
     * Retorna o identificador do tenant vinculado a este Job.
     */
    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    /**
     * Registra o TenantAwareJobMiddleware automaticamente para este Job.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new TenantAwareJobMiddleware,
        ];
    }
}
