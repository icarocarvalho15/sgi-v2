<?php

declare(strict_types=1);

namespace App\Modules\Production\DTOs;

final readonly class RecordProductionDTO
{
    public function __construct(
        public string $productionOrderId,
        public string $producedQuantity,
        public string $scrappedQuantity = '0.0000',
        public ?string $notes = null,
        public ?string $userId = null,
    ) {}
}
