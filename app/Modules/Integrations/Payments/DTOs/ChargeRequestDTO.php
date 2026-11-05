<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Payments\DTOs;

final readonly class ChargeRequestDTO
{
    public function __construct(
        public string $amount,
        public string $customerName,
        public string $customerDocument,
        public string $customerEmail,
        public string $dueDate,
        public string $description,
        public string $externalReference,
        public string $billingType = 'PIX', // PIX, BOLETO, CREDIT_CARD
        public ?string $postalCode = null,
        public ?string $addressNumber = null
    ) {}
}
