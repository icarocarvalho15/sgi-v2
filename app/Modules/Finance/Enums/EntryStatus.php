<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum EntryStatus: string
{
    case Pending = 'PENDING';
    case PartiallyPaid = 'PARTIALLY_PAID';
    case Paid = 'PAID';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::PartiallyPaid => 'Parcialmente Liquidado',
            self::Paid => 'Totalmente Liquidado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'text-amber-400 bg-amber-500/10 border-amber-500/20',
            self::PartiallyPaid => 'text-blue-400 bg-blue-500/10 border-blue-500/20',
            self::Paid => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
            self::Cancelled => 'text-rose-400 bg-rose-500/10 border-rose-500/20',
        };
    }
}
