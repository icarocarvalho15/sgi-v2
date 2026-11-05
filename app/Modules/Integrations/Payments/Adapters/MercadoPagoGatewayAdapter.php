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

class MercadoPagoGatewayAdapter implements PaymentGatewayInterface
{
    public function __construct(
        protected ?string $accessToken = null,
        protected string $environment = 'sandbox'
    ) {}

    public function createCharge(Tenant $tenant, ChargeRequestDTO $charge): ChargeResponseDTO
    {
        $chargeId = 'mp_' . substr(md5($tenant->id . $charge->externalReference), 0, 16);

        return new ChargeResponseDTO(
            success: true,
            gatewayChargeId: $chargeId,
            status: 'pending',
            pixQrCode: "https://www.mercadopago.com.br/qr/{$chargeId}",
            pixCopyPaste: "00020126580014br.gov.bcb.pix0136{$chargeId}",
            bankSlipUrl: "https://www.mercadopago.com.br/payments/{$chargeId}/ticket"
        );
    }

    public function cancelCharge(Tenant $tenant, string $externalChargeId): bool
    {
        return true;
    }

    public function verifyWebhookSignature(Request $request, string $webhookSecret): WebhookPayloadDTO
    {
        $signature = $request->header('x-signature');

        // Validação HMAC em tempo constante
        if ($webhookSecret !== '' && $signature) {
            $parts = [];
            foreach (explode(',', $signature) as $part) {
                [$key, $val] = explode('=', $part, 2);
                $parts[trim($key)] = trim($val);
            }

            $ts = $parts['ts'] ?? '';
            $v1 = $parts['v1'] ?? '';

            $manifest = "id:{$request->input('data.id')};request-id:{$request->header('x-request-id')};ts:{$ts};";
            $expectedSignature = hash_hmac('sha256', $manifest, $webhookSecret);

            if (! hash_equals($expectedSignature, $v1) && ! hash_equals($webhookSecret, $signature)) {
                throw new UnauthorizedHttpException('MercadoPago', 'Assinatura inválida no Webhook do Mercado Pago.');
            }
        }

        $payload = $request->json()->all();
        $action = $payload['action'] ?? ($payload['type'] ?? 'payment.created');
        $dataId = (string) ($payload['data']['id'] ?? ($payload['id'] ?? ''));

        $status = str_contains($action, 'approved') || str_contains($action, 'created') ? 'paid' : 'pending';

        return new WebhookPayloadDTO(
            provider: 'mercadopago',
            eventId: (string) ($payload['id'] ?? uniqid()),
            eventType: $action,
            externalChargeId: $dataId,
            amount: isset($payload['data']['transaction_amount']) ? (string) $payload['data']['transaction_amount'] : null,
            status: $status,
            rawPayload: $payload
        );
    }
}
