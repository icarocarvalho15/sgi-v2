<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\DTOs;

final readonly class ProvisionTenantDTO
{
    public function __construct(
        public string $name,
        public string $subdomain,
        public string $planId,
        public string $ownerName,
        public string $ownerEmail,
        public string $ownerPassword,
        public ?string $tradeName = null,
        public ?string $documentNumber = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            subdomain: strtolower(trim((string) $data['subdomain'])),
            planId: (string) $data['plan_id'],
            ownerName: (string) $data['owner_name'],
            ownerEmail: strtolower(trim((string) $data['owner_email'])),
            ownerPassword: (string) ($data['owner_password'] ?? 'password123'),
            tradeName: isset($data['trade_name']) ? (string) $data['trade_name'] : null,
            documentNumber: isset($data['document_number']) ? (string) $data['document_number'] : null,
        );
    }
}
