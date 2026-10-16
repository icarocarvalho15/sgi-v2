<?php

declare(strict_types=1);

namespace App\Modules\Production\DTOs;

use App\Modules\Production\Enums\ProductionOrderPriority;

final readonly class CreateProductionOrderDTO
{
    public function __construct(
        public string $itemId,
        public string $bomId,
        public string $warehouseId,
        public string $materialsWarehouseId,
        public string $plannedQuantity,
        public ProductionOrderPriority $priority = ProductionOrderPriority::Medium,
        public ?string $startDate = null,
        public ?string $dueDate = null,
        public ?string $notes = null,
    ) {}
}
