<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Gateways\Adapters;

use App\Modules\Fiscal\DTOs\ChargeResponseDTO;
use App\Modules\Fiscal\DTOs\CreateChargeDTO;
use App\Modules\Fiscal\DTOs\ParsedWebhookDTO;
use App\Modules\Fiscal\Gateways\Contracts\PaymentGatewayInterface;
use Carbon\Carbon;
use Illuminate\Support\Str;

final class StripeAdapter implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $apiKey = '',
        private readonly bool $isSandbox = true,
    ) {}

    public function createPixCharge(CreateChargeDTO $dto): ChargeResponseDTO
    {
        $id = 'pi_stripe_pix_'.Str::lower(Str::random(16));

        return new ChargeResponseDTO(
            success: true,
            transactionId: $id,
            gateway: 'STRIPE',
            paymentMethod: 'PIX',
            pixQrCodeUrl: "https://stripe.com/payments/qr/{$id}",
            pixCopiaECola: "000201010212stripe.com/pix/{$id}",
            rawResponse: [
                'id' => $id,
                'status' => 'requires_action',
                'amount' => (int) (((float) $dto->amount) * 100),
                'currency' => 'brl',
            ],
        );
    }

    public function createBoletoCharge(CreateChargeDTO $dto): ChargeResponseDTO
    {
        $id = 'pi_stripe_bol_'.Str::lower(Str::random(16));
        $barcode = '23793'.rand(10000, 99999).rand(10000, 99999).rand(10000, 99999);

        return new ChargeResponseDTO(
            success: true,
            transactionId: $id,
            gateway: 'STRIPE',
            paymentMethod: 'BOLETO',
            boletoBarcode: $barcode,
            boletoUrl: "https://stripe.com/boleto/{$id}",
            rawResponse: [
                'id' => $id,
                'status' => 'requires_action',
                'amount' => (int) (((float) $dto->amount) * 100),
            ],
        );
    }

    public function createCreditCardCharge(CreateChargeDTO $dto): ChargeResponseDTO
    {
        $id = 'cs_stripe_card_'.Str::lower(Str::random(16));

        return new ChargeResponseDTO(
            success: true,
            transactionId: $id,
            gateway: 'STRIPE',
            paymentMethod: 'CREDIT_CARD',
            cardPaymentUrl: "https://checkout.stripe.com/c/pay/{$id}",
            rawResponse: [
                'id' => $id,
                'status' => 'open',
                'url' => "https://checkout.stripe.com/c/pay/{$id}",
            ],
        );
    }

    public function verifyWebhookSignature(string $rawPayload, string $signatureHeader, string $secret): bool
    {
        if ($secret === '' || $signatureHeader === '') {
            return false;
        }

        // Stripe format: t=timestamp,v1=signature
        $timestamp = null;
        $signatures = [];

        $items = explode(',', $signatureHeader);
        foreach ($items as $item) {
            $parts = explode('=', trim($item), 2);
            if (count($parts) === 2) {
                if ($parts[0] === 't') {
                    $timestamp = $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatures[] = $parts[1];
                }
            }
        }

        if ($timestamp === null || empty($signatures)) {
            return false;
        }

        $signedPayload = "{$timestamp}.{$rawPayload}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                return true;
            }
        }

        return false;
    }

    public function parseWebhookPayload(array $payload): ParsedWebhookDTO
    {
        $eventType = (string) ($payload['type'] ?? 'unknown');
        $dataObject = $payload['data']['object'] ?? [];

        $isPaid = in_array($eventType, ['payment_intent.succeeded', 'charge.succeeded', 'checkout.session.completed'], true);

        $amountInCents = $dataObject['amount'] ?? ($dataObject['amount_total'] ?? 0);
        $amount = number_format(((float) $amountInCents) / 100, 2, '.', '');

        $entryId = $dataObject['metadata']['financial_entry_id'] ?? null;

        return new ParsedWebhookDTO(
            gateway: 'STRIPE',
            externalEventId: (string) ($payload['id'] ?? Str::uuid()->toString()),
            eventType: $eventType,
            financialEntryId: $entryId ? (string) $entryId : null,
            amount: $amount,
            paymentDate: Carbon::now()->toDateString(),
            isPaid: $isPaid,
            rawPayload: $payload,
        );
    }
}
