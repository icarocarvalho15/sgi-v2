<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Payments\DTOs;

final readonly class ChargeResponseDTO
{
    public function __construct(
        public bool $success,
        public ?string $gatewayChargeId = null,
        public ?string $status = null,
        public ?string $pixQrCode = null,
        public ?string $pixCopyPaste = null,
        public ?string $bankSlipUrl = null,
        public ?string $bankSlipBarcode = null,
        public ?string $errorMessage = null
    ) {}
}
