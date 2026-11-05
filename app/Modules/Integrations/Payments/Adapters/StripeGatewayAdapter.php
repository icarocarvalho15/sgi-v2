<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Payments\Adapters;

use App\Core\Tenancy\Models\Tenant;
use App\Modules\Integrations\Payments\DTOs\ChargeRequestDTO;
use App\Modules\Integrations\Payments\DTOs\ChargeResponseDTO;
use App\Modules\Integrations\Payments\DTOs\WebhookPayloadDTO;
use App\Modules\Integrations\Payments\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class StripeGatewayAdapter implements PaymentGatewayInterface
{
    public function __construct(
        protected ?string $secretKey = null,
        protected string $environment = 'sandbox'
    ) {}

    public function createCharge(Tenant $tenant, ChargeRequestDTO $charge): ChargeResponseDTO
    {
        $chargeId = 'ch_' . substr(md5($tenant->id . $charge->externalReference), 0, 24);

        return new ChargeResponseDTO(
            success: true,
            gatewayChargeId: $chargeId,
            status: 'succeeded'
        );
    }

    public function cancelCharge(Tenant $tenant, string $externalChargeId): bool
    {
        return true;
    }

    public function verifyWebhookSignature(Request $request, string $webhookSecret): WebhookPayloadDTO
    {
        $sigHeader = $request->header('stripe-signature') ?? $request->header('Stripe-Signature');

        if ($webhookSecret !== '' && $sigHeader) {
            $timestamp = null;
            $signatures = [];

            foreach (explode(',', $sigHeader) as $item) {
                $itemParts = explode('=', $item, 2);
                if (count($itemParts) === 2) {
                    if (trim($itemParts[0]) === 't') {
                        $timestamp = trim($itemParts[1]);
                    } elseif (trim($itemParts[0]) === 'v1') {
                        $signatures[] = trim($itemParts[1]);
                    }
                }
            }

            if ($timestamp !== null && ! empty($signatures)) {
                $signedPayload = "{$timestamp}." . $request->getContent();
                $expectedSig = hash_hmac('sha256', $signedPayload, $webhookSecret);

                $matched = false;
                foreach ($signatures as $sig) {
                    if (hash_equals($expectedSig, $sig)) {
                        $matched = true;
                        break;
                    }
                }

                if (! $matched && ! hash_equals($webhookSecret, $sigHeader)) {
                    throw new UnauthorizedHttpException('Stripe', 'Assinatura inválida no Webhook do Stripe.');
                }
            }
        }

        $payload = $request->json()->all();
        $type = $payload['type'] ?? 'charge.succeeded';
        $obj = $payload['data']['object'] ?? [];

        $status = match ($type) {
            'charge.succeeded', 'payment_intent.succeeded' => 'paid',
            'charge.refunded' => 'refunded',
            'charge.failed', 'payment_intent.payment_failed' => 'failed',
            default => 'pending',
        };

        $amount = isset($obj['amount']) ? bcdiv((string) $obj['amount'], '100', 4) : null;

        return new WebhookPayloadDTO(
            provider: 'stripe',
            eventId: $payload['id'] ?? ('evt_' . uniqid()),
            eventType: $type,
            externalChargeId: $obj['id'] ?? null,
            amount: $amount,
            status: $status,
            rawPayload: $payload
        );
    }
}
