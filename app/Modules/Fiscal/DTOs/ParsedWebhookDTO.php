<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\DTOs;

final readonly class ParsedWebhookDTO
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public string $gateway,
        public string $externalEventId,
        public string $eventType,
        public ?string $financialEntryId,
        public string $amount,
        public string $paymentDate,
        public bool $isPaid,
        public array $rawPayload = [],
    ) {}
}
