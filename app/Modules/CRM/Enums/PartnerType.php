<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

enum PartnerType: string
{
    case Customer = 'CUSTOMER';
    case Supplier = 'SUPPLIER';
    case Both = 'BOTH';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Cliente',
            self::Supplier => 'Fornecedor',
            self::Both => 'Cliente e Fornecedor',
        };
    }
}
