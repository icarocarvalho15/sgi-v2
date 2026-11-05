<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Exceptions;

use RuntimeException;

final class TenantContextMissingException extends RuntimeException
{
    public function __construct(string $message = 'Operação abortada: Contexto de Tenant não definido (Fail-Closed Zero-Trust policy).')
    {
        parent::__construct($message);
    }
}
