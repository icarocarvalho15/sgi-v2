<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

enum PaymentGateway: string
{
    case Asaas = 'ASAAS';
    case Stripe = 'STRIPE';
    case MercadoPago = 'MERCADOPAGO';

    public function label(): string
    {
        return match ($this) {
            self::Asaas => 'Asaas Pagamentos',
            self::Stripe => 'Stripe Global',
            self::MercadoPago => 'Mercado Pago',
        };
    }
}
