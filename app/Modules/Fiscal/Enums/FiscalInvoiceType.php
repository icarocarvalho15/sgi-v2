<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

enum FiscalInvoiceType: string
{
    case Nfe = 'NFE';
    case Nfse = 'NFSE';

    public function label(): string
    {
        return match ($this) {
            self::Nfe => 'NF-e (Produtos / Mercadorias)',
            self::Nfse => 'NFS-e (Serviços Municipais)',
        };
    }
}
