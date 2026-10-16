<?php

declare(strict_types=1);

namespace App\Modules\Inventory\DTOs;

use App\Modules\Inventory\Enums\KardexMovementType;
use App\Modules\Inventory\Exceptions\InvalidStockMovementException;

final readonly class StockAdjustmentDTO
{
    public function __construct(
        public string $warehouseId,
        public string $itemId,
        public string $direction, // 'IN' ou 'OUT'
        public string $quantity,
        public string $justification,
        public ?string $unitCost = null,
        public ?string $userId = null
    ) {
        if (! in_array($this->direction, ['IN', 'OUT'], true)) {
            throw new InvalidStockMovementException('A direção do ajuste deve ser IN ou OUT.');
        }

        if (trim($this->justification) === '') {
            throw new InvalidStockMovementException('A justificativa é estritamente obrigatória para ajustes de estoque.');
        }

        if (bccomp($this->quantity, '0', 4) <= 0) {
            throw new InvalidStockMovementException('A quantidade de ajuste deve ser positiva.');
        }
    }

    public function toMovementType(): KardexMovementType
    {
        return $this->direction === 'IN'
            ? KardexMovementType::InAdjustment
            : KardexMovementType::OutAdjustment;
    }
}
