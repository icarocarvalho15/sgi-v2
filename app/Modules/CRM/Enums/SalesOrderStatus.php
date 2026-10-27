<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

enum SalesOrderStatus: string
{
    case Confirmed = 'CONFIRMED';
    case InProduction = 'IN_PRODUCTION';
    case Shipped = 'SHIPPED';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmado',
            self::InProduction => 'Em Produção',
            self::Shipped => 'Expedido / Em Trânsito',
            self::Completed => 'Concluído / Entregue',
            self::Cancelled => 'Cancelado',
        };
    }
}
