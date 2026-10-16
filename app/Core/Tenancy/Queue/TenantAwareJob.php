<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Queue;

interface TenantAwareJob
{
    /**
     * Retorna o UUID do tenant associado a este job de fila.
     */
    public function getTenantId(): string;
}
