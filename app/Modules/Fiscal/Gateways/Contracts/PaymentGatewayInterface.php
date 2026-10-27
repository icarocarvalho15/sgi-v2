<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Gateways\Contracts;

use App\Modules\Fiscal\DTOs\ChargeResponseDTO;
use App\Modules\Fiscal\DTOs\CreateChargeDTO;
use App\Modules\Fiscal\DTOs\ParsedWebhookDTO;

interface PaymentGatewayInterface
{
    public function createPixCharge(CreateChargeDTO $dto): ChargeResponseDTO;

    public function createBoletoCharge(CreateChargeDTO $dto): ChargeResponseDTO;

    public function createCreditCardCharge(CreateChargeDTO $dto): ChargeResponseDTO;

    public function verifyWebhookSignature(string $rawPayload, string $signatureHeader, string $secret): bool;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parseWebhookPayload(array $payload): ParsedWebhookDTO;
}
