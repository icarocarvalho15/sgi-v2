<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\DTOs;

final readonly class CreateChargeDTO
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $amount,
        public string $description,
        public string $dueDate,
        public string $customerName,
        public string $customerDocument,
        public ?string $customerEmail = null,
        public ?string $customerPhone = null,
        public array $metadata = [],
    ) {}
}
