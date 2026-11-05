<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Payments;

use App\Core\Tenancy\Models\Tenant;
use App\Modules\Integrations\Payments\DTOs\ChargeRequestDTO;
use App\Modules\Integrations\Payments\DTOs\ChargeResponseDTO;
use App\Modules\Integrations\Payments\DTOs\WebhookPayloadDTO;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Cria uma cobrança (Pix, Boleto ou Cartão).
     */
    public function createCharge(Tenant $tenant, ChargeRequestDTO $charge): ChargeResponseDTO;

    /**
     * Cancela uma cobrança no gateway.
     */
    public function cancelCharge(Tenant $tenant, string $externalChargeId): bool;

    /**
     * Valida a assinatura do webhook e extrai os dados normalizados.
     */
    public function verifyWebhookSignature(Request $request, string $webhookSecret): WebhookPayloadDTO;
}
