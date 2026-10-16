<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Exceptions;

use RuntimeException;

final class CrossTenantTamperingException extends RuntimeException
{
    public function __construct(string $message = 'Tentativa de violação de isolamento multi-tenant: alteração de tenant_id não é permitida.')
    {
        parent::__construct($message, 403);
    }
}
