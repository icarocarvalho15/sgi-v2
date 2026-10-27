<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Gateways\Adapters;

use App\Modules\Fiscal\DTOs\ChargeResponseDTO;
use App\Modules\Fiscal\DTOs\CreateChargeDTO;
use App\Modules\Fiscal\DTOs\ParsedWebhookDTO;
use App\Modules\Fiscal\Gateways\Contracts\PaymentGatewayInterface;
use Carbon\Carbon;
use Illuminate\Support\Str;

final class AsaasAdapter implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $apiKey = '',
        private readonly bool $isSandbox = true,
    ) {}

    public function createPixCharge(CreateChargeDTO $dto): ChargeResponseDTO
    {
        $id = 'pay_asaas_pix_'.Str::lower(Str::random(12));
        $copiaCola = '00020101021226870014br.gov.bcb.pix2565pix.asaas.com/qr/v2/'.Str::random(24);

        return new ChargeResponseDTO(
            success: true,
            transactionId: $id,
            gateway: 'ASAAS',
            paymentMethod: 'PIX',
            pixQrCodeUrl: "https://sandbox.asaas.com/api/v3/payments/{$id}/pixQrCode",
            pixCopiaECola: $copiaCola,
            rawResponse: [
                'id' => $id,
                'status' => 'PENDING',
                'billingType' => 'PIX',
                'value' => (float) $dto->amount,
                'dueDate' => $dto->dueDate,
            ],
        );
    }

    public function createBoletoCharge(CreateChargeDTO $dto): ChargeResponseDTO
    {
        $id = 'pay_asaas_bol_'.Str::lower(Str::random(12));
        $barcode = '03399'.rand(10000, 99999).rand(10000, 99999).rand(10000, 99999).rand(10000, 99999);

        return new ChargeResponseDTO(
            success: true,
            transactionId: $id,
            gateway: 'ASAAS',
            paymentMethod: 'BOLETO',
            boletoBarcode: $barcode,
            boletoUrl: "https://sandbox.asaas.com/b/pdf/{$id}",
            rawResponse: [
                'id' => $id,
                'status' => 'PENDING',
                'billingType' => 'BOLETO',
                'value' => (float) $dto->amount,
                'dueDate' => $dto->dueDate,
            ],
        );
    }

    public function createCreditCardCharge(CreateChargeDTO $dto): ChargeResponseDTO
    {
        $id = 'pay_asaas_card_'.Str::lower(Str::random(12));

        return new ChargeResponseDTO(
            success: true,
            transactionId: $id,
            gateway: 'ASAAS',
            paymentMethod: 'CREDIT_CARD',
            cardPaymentUrl: "https://sandbox.asaas.com/invoice/{$id}",
            rawResponse: [
                'id' => $id,
                'status' => 'PENDING',
                'billingType' => 'CREDIT_CARD',
                'value' => (float) $dto->amount,
            ],
        );
    }

    public function verifyWebhookSignature(string $rawPayload, string $signatureHeader, string $secret): bool
    {
        if ($secret === '' || $signatureHeader === '') {
            return false;
        }

        // Asaas usa token de autenticação direto no header (asaas-access-token)
        return hash_equals($secret, $signatureHeader);
    }

    public function parseWebhookPayload(array $payload): ParsedWebhookDTO
    {
        $event = $payload['event'] ?? 'UNKNOWN';
        $payment = $payload['payment'] ?? [];

        $isPaid = in_array($event, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'], true);
        $amount = isset($payment['value']) ? number_format((float) $payment['value'], 2, '.', '') : '0.00';
        $entryId = $payment['externalReference'] ?? ($payment['metadata']['financial_entry_id'] ?? null);

        return new ParsedWebhookDTO(
            gateway: 'ASAAS',
            externalEventId: (string) ($payload['id'] ?? ($payment['id'] ?? Str::uuid()->toString())),
            eventType: (string) $event,
            financialEntryId: $entryId ? (string) $entryId : null,
            amount: $amount,
            paymentDate: (string) ($payment['paymentDate'] ?? Carbon::now()->toDateString()),
            isPaid: $isPaid,
            rawPayload: $payload,
        );
    }
}
