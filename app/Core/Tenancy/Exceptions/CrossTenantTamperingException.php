<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Exceptions;

use RuntimeException;

final class CrossTenantTamperingException extends RuntimeException
{
    public function __construct(string $message = 'Violação de segurança crítica: Tentativa de alteração de tenant_id detectada (Cross-Tenant Tampering).')
    {
        parent::__construct($message);
    }
}
