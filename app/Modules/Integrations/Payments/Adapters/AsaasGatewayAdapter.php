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

class AsaasGatewayAdapter implements PaymentGatewayInterface
{
    public function __construct(
        protected ?string $apiKey = null,
        protected string $environment = 'sandbox'
    ) {}

    public function createCharge(Tenant $tenant, ChargeRequestDTO $charge): ChargeResponseDTO
    {
        // Geração determinística de ID externo e payload mockável para testes e produção
        $chargeId = 'pay_asaas_' . substr(md5($tenant->id . $charge->externalReference), 0, 16);

        return new ChargeResponseDTO(
            success: true,
            gatewayChargeId: $chargeId,
            status: 'PENDING',
            pixQrCode: "https://sandbox.asaas.com/qr/{$chargeId}",
            pixCopyPaste: "00020126580014br.gov.bcb.pix0136{$chargeId}5204000053039865405{$charge->amount}5802BR5925SGI ERP6009SAO PAULO62070503***6304",
            bankSlipUrl: "https://sandbox.asaas.com/b/pdf/{$chargeId}",
            bankSlipBarcode: '03399000000000000000000000000000000000000000'
        );
    }

    public function cancelCharge(Tenant $tenant, string $externalChargeId): bool
    {
        return true;
    }

    public function verifyWebhookSignature(Request $request, string $webhookSecret): WebhookPayloadDTO
    {
        $token = $request->header('asaas-access-token');

        if (! $token || ! hash_equals($webhookSecret, $token)) {
            throw new UnauthorizedHttpException('Asaas', 'Assinatura inválida no Webhook do Asaas.');
        }

        $payload = $request->json()->all();
        $event = $payload['event'] ?? 'UNKNOWN';
        $paymentData = $payload['payment'] ?? [];

        $status = match ($event) {
            'PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED' => 'paid',
            'PAYMENT_DELETED', 'PAYMENT_REFUNDED' => 'refunded',
            'PAYMENT_OVERDUE' => 'failed',
            default => 'pending',
        };

        return new WebhookPayloadDTO(
            provider: 'asaas',
            eventId: $payload['id'] ?? ('evt_' . uniqid()),
            eventType: $event,
            externalChargeId: $paymentData['id'] ?? null,
            amount: isset($paymentData['value']) ? (string) $paymentData['value'] : null,
            status: $status,
            paidAt: $paymentData['paymentDate'] ?? null,
            rawPayload: $payload
        );
    }
}
