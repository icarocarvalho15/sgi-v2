<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

enum QuoteStatus: string
{
    case Draft = 'DRAFT';
    case Sent = 'SENT';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Converted = 'CONVERTED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Sent => 'Enviado ao Cliente',
            self::Approved => 'Aprovado',
            self::Rejected => 'Recusado',
            self::Converted => 'Convertido em Pedido',
        };
    }
}
