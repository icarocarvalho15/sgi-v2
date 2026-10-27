<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Models;

use App\Core\Security\VaultEncryption;
use App\Core\Tenancy\Models\TenantModel;
use App\Modules\Fiscal\Enums\PaymentGateway;

final class PaymentGatewaySetting extends TenantModel
{
    protected $table = 'payment_gateway_settings';

    protected $fillable = [
        'gateway',
        'api_key',
        'webhook_secret',
        'is_sandbox',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'gateway' => PaymentGateway::class,
        'is_sandbox' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function setEncryptedApiKey(string $plainApiKey): void
    {
        $vault = app(VaultEncryption::class);
        $this->api_key = $vault->encrypt($plainApiKey, $this->tenant_id, 'gateway_api_key');
    }

    public function getDecryptedApiKey(): string
    {
        $vault = app(VaultEncryption::class);

        return $vault->decrypt($this->api_key, $this->tenant_id, 'gateway_api_key');
    }

    public function setEncryptedWebhookSecret(?string $plainSecret): void
    {
        if ($plainSecret === null || $plainSecret === '') {
            $this->webhook_secret = null;

            return;
        }

        $vault = app(VaultEncryption::class);
        $this->webhook_secret = $vault->encrypt($plainSecret, $this->tenant_id, 'gateway_webhook_secret');
    }

    public function getDecryptedWebhookSecret(): ?string
    {
        if (empty($this->webhook_secret)) {
            return null;
        }

        $vault = app(VaultEncryption::class);

        return $vault->decrypt($this->webhook_secret, $this->tenant_id, 'gateway_webhook_secret');
    }
}
