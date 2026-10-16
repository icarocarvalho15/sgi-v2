<?php

declare(strict_types=1);

namespace App\Modules\Production\DTOs;

final readonly class CreateBomDTO
{
    /**
     * @param  array<int, array{item_id: string, unit_of_measure_id: string, quantity: string, scrap_rate_percentage?: string|null, notes?: string|null}>  $items
     * @param  array<int, array{work_center_id: string, sequence: int, name: string, description?: string|null, setup_time_minutes?: string|null, run_time_minutes_per_unit?: string|null}>  $routingSteps
     */
    public function __construct(
        public string $itemId,
        public string $code,
        public int $version = 1,
        public ?string $description = null,
        public string $batchSize = '1.0000',
        public bool $isActive = true,
        public bool $isDefault = true,
        public array $items = [],
        public array $routingSteps = [],
    ) {}
}
