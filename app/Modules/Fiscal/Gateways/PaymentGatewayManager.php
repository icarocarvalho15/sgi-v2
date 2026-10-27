<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Gateways;

use App\Modules\Fiscal\Enums\PaymentGateway;
use App\Modules\Fiscal\Gateways\Adapters\AsaasAdapter;
use App\Modules\Fiscal\Gateways\Adapters\MercadoPagoAdapter;
use App\Modules\Fiscal\Gateways\Adapters\StripeAdapter;
use App\Modules\Fiscal\Gateways\Contracts\PaymentGatewayInterface;
use App\Modules\Fiscal\Models\PaymentGatewaySetting;

final class PaymentGatewayManager
{
    public function resolve(PaymentGatewaySetting $setting): PaymentGatewayInterface
    {
        $apiKey = $setting->getDecryptedApiKey();

        return match ($setting->gateway) {
            PaymentGateway::Asaas => new AsaasAdapter($apiKey, $setting->is_sandbox),
            PaymentGateway::Stripe => new StripeAdapter($apiKey, $setting->is_sandbox),
            PaymentGateway::MercadoPago => new MercadoPagoAdapter($apiKey, $setting->is_sandbox),
        };
    }

    public function resolveByGatewayEnum(PaymentGateway $gateway, string $apiKey, bool $isSandbox = true): PaymentGatewayInterface
    {
        return match ($gateway) {
            PaymentGateway::Asaas => new AsaasAdapter($apiKey, $isSandbox),
            PaymentGateway::Stripe => new StripeAdapter($apiKey, $isSandbox),
            PaymentGateway::MercadoPago => new MercadoPagoAdapter($apiKey, $isSandbox),
        };
    }
}
