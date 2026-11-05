<?php

declare(strict_types=1);

namespace App\Core\Security\Exceptions;

use RuntimeException;

final class VaultDecryptionException extends RuntimeException
{
    public function __construct(string $message = 'Falha na descriptografia do cofre: Chave de Tenant inválida ou payload violado (AES-256-GCM verification failed).')
    {
        parent::__construct($message);
    }
}
