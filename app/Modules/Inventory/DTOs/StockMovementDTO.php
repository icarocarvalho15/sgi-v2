<?php

declare(strict_types=1);

namespace App\Modules\Inventory\DTOs;

use App\Modules\Inventory\Enums\KardexMovementType;
use App\Modules\Inventory\Exceptions\InvalidStockMovementException;

final readonly class StockMovementDTO
{
    public function __construct(
        public string $warehouseId,
        public string $itemId,
        public KardexMovementType $movementType,
        public string $quantity,
        public ?string $unitCost = null,
        public ?string $referenceType = null,
        public ?string $referenceId = null,
        public ?string $documentNumber = null,
        public ?string $justification = null,
        public ?string $userId = null
    ) {
        if (bccomp($this->quantity, '0', 4) <= 0) {
            throw new InvalidStockMovementException('A quantidade movimentada deve ser maior que zero.');
        }

        if ($this->unitCost !== null && bccomp($this->unitCost, '0', 4) < 0) {
            throw new InvalidStockMovementException('O custo unitário não pode ser negativo.');
        }
    }
}
