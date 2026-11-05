<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        string $itemName,
        string $requested,
        string $available,
        string $warehouseName = ''
    ) {
        $message = sprintf(
            'Saldo insuficiente para o item "%s". Solicitado: %s, Disponível: %s%s.',
            $itemName,
            $requested,
            $available,
            $warehouseName !== '' ? " no depósito {$warehouseName}" : ''
        );

        parent::__construct($message);
    }
}
