<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\DTOs;

final readonly class ChargeResponseDTO
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public bool $success,
        public string $transactionId,
        public string $gateway,
        public string $paymentMethod,
        public ?string $pixQrCodeUrl = null,
        public ?string $pixCopiaECola = null,
        public ?string $boletoBarcode = null,
        public ?string $boletoUrl = null,
        public ?string $cardPaymentUrl = null,
        public ?string $errorMessage = null,
        public array $rawResponse = [],
    ) {}
}
