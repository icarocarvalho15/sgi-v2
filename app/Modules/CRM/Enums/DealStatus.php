<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

enum DealStatus: string
{
    case Open = 'OPEN';
    case Won = 'WON';
    case Lost = 'LOST';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Em Aberto',
            self::Won => 'Ganha',
            self::Lost => 'Perdida',
        };
    }
}
