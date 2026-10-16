<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Exceptions;

use RuntimeException;

final class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly string $itemId,
        public readonly string $warehouseId,
        public readonly string $requestedQty,
        public readonly string $availableQty
    ) {
        parent::__construct(
            "Saldo insuficiente no almoxarifado {$warehouseId} para o item {$itemId}. Solicitado: {$requestedQty}, Disponível: {$availableQty}."
        );
    }
}
