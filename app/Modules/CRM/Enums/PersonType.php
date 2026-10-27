<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

enum PersonType: string
{
    case LegalEntity = 'LEGAL_ENTITY';
    case Individual = 'INDIVIDUAL';

    public function label(): string
    {
        return match ($this) {
            self::LegalEntity => 'Pessoa Jurídica (CNPJ)',
            self::Individual => 'Pessoa Física (CPF)',
        };
    }
}
