<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Queue;

use App\Core\Tenancy\TenantContext;
use Closure;
use Throwable;

final class TenantAwareJobMiddleware
{
    /**
     * Processa o Job na fila inicializando o contexto do Tenant e garantindo a limpeza no finally.
     *
     * @param  Closure(object): void  $next
     *
     * @throws Throwable
     */
    public function handle(object $job, Closure $next): void
    {
        $context = app(TenantContext::class);

        $tenantId = null;

        if ($job instanceof TenantAwareJob) {
            $tenantId = $job->getTenantId();
        } elseif (property_exists($job, 'tenantId') && is_string($job->tenantId)) {
            $tenantId = $job->tenantId;
        }

        if ($tenantId !== null && $tenantId !== '') {
            $context->setById($tenantId);
        }

        try {
            $next($job);
        } finally {
            $context->clear();
        }
    }
}
