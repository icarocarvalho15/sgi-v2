<?php

declare(strict_types=1);

namespace App\Modules\Production\Enums;

enum ProductionOrderStatus: string
{
    case Draft = 'DRAFT';
    case Released = 'RELEASED';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Released => 'Liberada',
            self::InProgress => 'Em Produção',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }

    public function canRelease(): bool
    {
        return $this === self::Draft;
    }

    public function canProduce(): bool
    {
        return in_array($this, [self::Released, self::InProgress], true);
    }
}
