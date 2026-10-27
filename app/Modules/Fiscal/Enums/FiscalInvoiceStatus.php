<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

enum FiscalInvoiceStatus: string
{
    case Draft = 'DRAFT';
    case Processing = 'PROCESSING';
    case Authorized = 'AUTHORIZED';
    case Rejected = 'REJECTED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Processing => 'Processando na SEFAZ',
            self::Authorized => 'Autorizada (Emitida)',
            self::Rejected => 'Rejeitada pela SEFAZ',
            self::Cancelled => 'Cancelada',
        };
    }
}
