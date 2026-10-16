<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Exceptions;

use RuntimeException;

final class TenantContextMissingException extends RuntimeException
{
    public function __construct(string $message = 'Nenhum contexto de tenant ativo encontrado para executar a operação com fail-closed.')
    {
        parent::__construct($message, 403);
    }
}
