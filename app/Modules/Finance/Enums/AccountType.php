<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum AccountType: string
{
    case Checking = 'CHECKING';
    case Cash = 'CASH';
    case Savings = 'SAVINGS';
    case Investment = 'INVESTMENT';

    public function label(): string
    {
        return match ($this) {
            self::Checking => 'Conta Corrente',
            self::Cash => 'Caixa Físico / Cofre',
            self::Savings => 'Conta Poupança',
            self::Investment => 'Aplicação / Investimento',
        };
    }
}
