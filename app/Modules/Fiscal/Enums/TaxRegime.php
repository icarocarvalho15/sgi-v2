<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

enum TaxRegime: string
{
    case SimplesNacional = 'SIMPLES_NACIONAL';
    case LucroPresumido = 'LUCRO_PRESUMIDO';
    case LucroReal = 'LUCRO_REAL';

    public function label(): string
    {
        return match ($this) {
            self::SimplesNacional => 'Simples Nacional',
            self::LucroPresumido => 'Lucro Presumido',
            self::LucroReal => 'Lucro Real',
        };
    }
}
