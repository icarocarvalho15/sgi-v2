<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Payments;

use App\Core\Tenancy\Models\Tenant;
use App\Modules\Integrations\Models\PaymentGatewayConfig;
use App\Modules\Integrations\Payments\Adapters\AsaasGatewayAdapter;
use App\Modules\Integrations\Payments\Adapters\MercadoPagoGatewayAdapter;
use App\Modules\Integrations\Payments\Adapters\StripeGatewayAdapter;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    /**
     * Instancia o adapter do gateway configurado para o Tenant com credenciais descriptografadas.
     */
    public static function make(Tenant $tenant, ?string $gatewayName = null): PaymentGatewayInterface
    {
        $query = PaymentGatewayConfig::where('tenant_id', $tenant->id);

        if ($gatewayName) {
            $query->where('gateway_name', $gatewayName);
        } else {
            $query->where('is_active', true);
        }

        /** @var PaymentGatewayConfig|null $config */
        $config = $query->first();

        $name = $config?->gateway_name ?? ($gatewayName ?? 'asaas');
        $apiKey = $config?->getDecryptedApiKey();
        $mode = $config?->mode ?? 'sandbox';

        return match ($name) {
            'asaas' => new AsaasGatewayAdapter($apiKey, $mode),
            'mercadopago' => new MercadoPagoGatewayAdapter($apiKey, $mode),
            'stripe' => new StripeGatewayAdapter($apiKey, $mode),
            default => throw new InvalidArgumentException("Gateway não suportado: {$name}"),
        };
    }
}
