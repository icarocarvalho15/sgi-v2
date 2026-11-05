<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Jobs;

use App\Core\Tenancy\Exceptions\TenantContextMissingException;
use App\Core\Tenancy\TenantContext;

trait InteractsWithTenantQueue
{
    /**
     * Serialized tenant identifier for queue execution.
     */
    public string $tenantId;

    /**
     * Captures active TenantContext at dispatch time.
     */
    public function captureTenantContext(): void
    {
        $currentId = TenantContext::id();

        if (empty($currentId)) {
            throw new TenantContextMissingException(
                sprintf('Não é possível despachar o Job [%s] sem TenantContext ativo.', static::class)
            );
        }

        $this->tenantId = $currentId;
    }

    /**
     * Implementation of TenantAwareJob interface.
     */
    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    /**
     * Middleware for queue worker execution.
     * Sets tenant context and PostgreSQL session before handle(), and resets in finally.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new class($this->tenantId) {
                public function __construct(private readonly string $tenantId) {}

                public function handle(mixed $job, callable $next): mixed
                {
                    TenantContext::set($this->tenantId);

                    if (function_exists('setPermissionsTeamId')) {
                        setPermissionsTeamId($this->tenantId);
                    }

                    try {
                        return $next($job);
                    } finally {
                        TenantContext::clear();
                    }
                }
            },
        ];
    }
}
