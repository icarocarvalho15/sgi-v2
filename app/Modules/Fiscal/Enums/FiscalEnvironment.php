<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

enum FiscalEnvironment: string
{
    case Homologation = 'HOMOLOGATION';
    case Production = 'PRODUCTION';

    public function label(): string
    {
        return match ($this) {
            self::Homologation => 'Homologação (Testes SEFAZ)',
            self::Production => 'Produção (Valor Fiscal)',
        };
    }
}
