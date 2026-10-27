<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTOs;

use App\Modules\Finance\Enums\PaymentMethod;

final readonly class SettleFinancialPaymentDTO
{
    public function __construct(
        public string $financialEntryId,
        public string $financialAccountId,
        public PaymentMethod $paymentMethod,
        public string $paymentDate,
        public string $amount,
        public string $interestPaid = '0.00',
        public string $finePaid = '0.00',
        public string $discountApplied = '0.00',
        public ?string $notes = null,
    ) {}
}
