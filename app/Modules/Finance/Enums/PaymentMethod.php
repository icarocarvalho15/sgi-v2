<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum PaymentMethod: string
{
    case Pix = 'PIX';
    case Boleto = 'BOLETO';
    case CreditCard = 'CREDIT_CARD';
    case BankTransfer = 'BANK_TRANSFER';
    case Cash = 'CASH';

    public function label(): string
    {
        return match ($this) {
            self::Pix => 'Pix',
            self::Boleto => 'Boleto Bancário',
            self::CreditCard => 'Cartão de Crédito',
            self::BankTransfer => 'Transferência / TED',
            self::Cash => 'Dinheiro / Espécie',
        };
    }
}
