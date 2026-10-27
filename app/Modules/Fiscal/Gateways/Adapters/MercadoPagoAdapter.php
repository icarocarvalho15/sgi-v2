<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Gateways\Adapters;

use App\Modules\Fiscal\DTOs\ChargeResponseDTO;
use App\Modules\Fiscal\DTOs\CreateChargeDTO;
use App\Modules\Fiscal\DTOs\ParsedWebhookDTO;
use App\Modules\Fiscal\Gateways\Contracts\PaymentGatewayInterface;
use Carbon\Carbon;
use Illuminate\Support\Str;

final class MercadoPagoAdapter implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $apiKey = '',
        private readonly bool $isSandbox = true,
    ) {}

    public function createPixCharge(CreateChargeDTO $dto): ChargeResponseDTO
    {
        $id = (string) rand(1000000000, 9999999999);
        $copiaCola = '00020126580014br.gov.bcb.pix0136'.Str::uuid()->toString();

        return new ChargeResponseDTO(
            success: true,
            transactionId: $id,
            gateway: 'MERCADOPAGO',
            paymentMethod: 'PIX',
            pixQrCodeUrl: "https://www.mercadopago.com.br/payments/{$id}/ticket",
            pixCopiaECola: $copiaCola,
            rawResponse: [
                'id' => $id,
                'status' => 'pending',
                'payment_method_id' => 'pix',
                'transaction_amount' => (float) $dto->amount,
            ],
        );
    }

    public function createBoletoCharge(CreateChargeDTO $dto): ChargeResponseDTO
    {
        $id = (string) rand(1000000000, 9999999999);
        $barcode = '23793'.rand(10000, 99999).rand(10000, 99999).rand(10000, 99999);

        return new ChargeResponseDTO(
            success: true,
            transactionId: $id,
            gateway: 'MERCADOPAGO',
            paymentMethod: 'BOLETO',
            boletoBarcode: $barcode,
            boletoUrl: "https://www.mercadopago.com.br/payments/{$id}/ticket",
            rawResponse: [
                'id' => $id,
                'status' => 'pending',
                'payment_method_id' => 'bolbradesco',
                'transaction_amount' => (float) $dto->amount,
            ],
        );
    }

    public function createCreditCardCharge(CreateChargeDTO $dto): ChargeResponseDTO
    {
        $id = (string) rand(1000000000, 9999999999);

        return new ChargeResponseDTO(
            success: true,
            transactionId: $id,
            gateway: 'MERCADOPAGO',
            paymentMethod: 'CREDIT_CARD',
            cardPaymentUrl: "https://www.mercadopago.com.br/checkout/v1/redirect?pref_id={$id}",
            rawResponse: [
                'id' => $id,
                'status' => 'pending',
                'init_point' => "https://www.mercadopago.com.br/checkout/v1/redirect?pref_id={$id}",
            ],
        );
    }

    public function verifyWebhookSignature(string $rawPayload, string $signatureHeader, string $secret): bool
    {
        if ($secret === '' || $signatureHeader === '') {
            return false;
        }

        // MercadoPago format: ts=...,v1=...
        $ts = null;
        $hash = null;

        $parts = explode(',', $signatureHeader);
        foreach ($parts as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                if ($kv[0] === 'ts') {
                    $ts = $kv[1];
                } elseif ($kv[0] === 'v1') {
                    $hash = $kv[1];
                }
            }
        }

        if ($ts === null || $hash === null) {
            return false;
        }

        $expected = hash_hmac('sha256', "ts:{$ts}|data:{$rawPayload}", $secret);

        return hash_equals($expected, $hash);
    }

    public function parseWebhookPayload(array $payload): ParsedWebhookDTO
    {
        $action = (string) ($payload['action'] ?? ($payload['type'] ?? 'unknown'));
        $data = $payload['data'] ?? [];
        $dataId = (string) ($data['id'] ?? ($payload['id'] ?? Str::uuid()->toString()));

        $isPaid = in_array($action, ['payment.created', 'payment.updated'], true)
            && ($data['status'] ?? '') === 'approved';

        $amount = isset($data['transaction_amount'])
            ? number_format((float) $data['transaction_amount'], 2, '.', '')
            : '0.00';

        $entryId = $data['metadata']['financial_entry_id'] ?? ($payload['metadata']['financial_entry_id'] ?? null);

        return new ParsedWebhookDTO(
            gateway: 'MERCADOPAGO',
            externalEventId: (string) ($payload['id'] ?? $dataId),
            eventType: $action,
            financialEntryId: $entryId ? (string) $entryId : null,
            amount: $amount,
            paymentDate: Carbon::now()->toDateString(),
            isPaid: $isPaid,
            rawPayload: $payload,
        );
    }
}
