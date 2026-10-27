<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTOs;

use App\Modules\Finance\Enums\EntryType;

final readonly class CreateFinancialEntryDTO
{
    public function __construct(
        public EntryType $entryType,
        public string $chartOfAccountId,
        public string $documentNumber,
        public string $title,
        public string $totalAmount,
        public string $firstDueDate,
        public ?string $partnerId = null,
        public ?string $costCenterId = null,
        public ?string $salesOrderId = null,
        public int $installmentsCount = 1,
        public ?string $issueDate = null,
        public ?string $competenceDate = null,
        public int $intervalDays = 30,
        public ?string $notes = null,
    ) {}
}
