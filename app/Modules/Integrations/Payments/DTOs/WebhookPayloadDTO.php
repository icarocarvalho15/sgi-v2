<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Payments\DTOs;

final readonly class WebhookPayloadDTO
{
    public function __construct(
        public string $provider,
        public string $eventId,
        public string $eventType,
        public ?string $externalChargeId,
        public ?string $amount,
        public string $status, // paid, pending, failed, refunded
        public ?string $paidAt = null,
        public array $rawPayload = []
    ) {}
}
