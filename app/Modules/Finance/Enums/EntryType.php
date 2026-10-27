<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum EntryType: string
{
    case Receivable = 'RECEIVABLE';
    case Payable = 'PAYABLE';

    public function label(): string
    {
        return match ($this) {
            self::Receivable => 'Conta a Receber',
            self::Payable => 'Conta a Pagar',
        };
    }
}
