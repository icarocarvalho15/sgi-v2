<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum AccountCategoryType: string
{
    case Revenue = 'REVENUE';
    case Expense = 'EXPENSE';

    public function label(): string
    {
        return match ($this) {
            self::Revenue => 'Receita',
            self::Expense => 'Despesa',
        };
    }
}
