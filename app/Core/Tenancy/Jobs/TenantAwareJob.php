<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Jobs;

interface TenantAwareJob
{
    /**
     * Get the tenant ID that owns this background job.
     */
    public function getTenantId(): string;
}
