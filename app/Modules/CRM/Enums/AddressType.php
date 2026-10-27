<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

enum AddressType: string
{
    case Main = 'MAIN';
    case Billing = 'BILLING';
    case Shipping = 'SHIPPING';

    public function label(): string
    {
        return match ($this) {
            self::Main => 'Principal',
            self::Billing => 'Cobrança',
            self::Shipping => 'Entrega',
        };
    }
}
