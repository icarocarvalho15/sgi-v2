<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

enum DealPriority: string
{
    case Low = 'LOW';
    case Medium = 'MEDIUM';
    case High = 'HIGH';
    case Urgent = 'URGENT';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baixa',
            self::Medium => 'Média',
            self::High => 'Alta',
            self::Urgent => 'Urgente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'text-slate-400 bg-slate-800',
            self::Medium => 'text-blue-400 bg-blue-950',
            self::High => 'text-amber-400 bg-amber-950',
            self::Urgent => 'text-rose-400 bg-rose-950',
        };
    }
}
