<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fiscal\Enums\PaymentGateway;
use App\Modules\Fiscal\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhookService,
    ) {}

    public function handle(Request $request, string $gateway): JsonResponse
    {
        $gatewayEnum = PaymentGateway::tryFrom(strtoupper($gateway));
        if (! $gatewayEnum) {
            throw new BadRequestHttpException("Gateway {$gateway} não suportado.");
        }

        $signatureHeader = match ($gatewayEnum) {
            PaymentGateway::Asaas => (string) $request->header('asaas-access-token', ''),
            PaymentGateway::Stripe => (string) $request->header('Stripe-Signature', ''),
            PaymentGateway::MercadoPago => (string) $request->header('x-signature', ''),
        };

        $rawPayload = (string) $request->getContent();
        $payload = $request->all();

        $result = $this->webhookService->handle(
            gateway: $gatewayEnum,
            rawPayload: $rawPayload,
            signatureHeader: $signatureHeader,
            payload: $payload,
        );

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }
}
