<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Models;

use App\Core\Security\VaultEncryption;
use App\Core\Tenancy\Models\TenantModel;

/**
 * Configuração de Gateway de Pagamento do Tenant.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $gateway_name
 * @property bool $is_active
 * @property string|null $api_key_encrypted
 * @property string|null $webhook_secret_encrypted
 * @property string $mode
 * @property array|null $settings
 */
class PaymentGatewayConfig extends TenantModel
{
    protected $table = 'payment_gateways_config';

    protected $fillable = [
        'gateway_name',
        'is_active',
        'api_key_encrypted',
        'webhook_secret_encrypted',
        'mode',
        'settings',
    ];

    protected $hidden = [
        'api_key_encrypted',
        'webhook_secret_encrypted',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function setApiKey(string $apiKey): void
    {
        $this->api_key_encrypted = VaultEncryption::encrypt($apiKey, $this->tenant_id, "gateway_{$this->gateway_name}_key");
    }

    public function getDecryptedApiKey(): ?string
    {
        if (empty($this->api_key_encrypted)) {
            return null;
        }

        return VaultEncryption::decrypt($this->api_key_encrypted, $this->tenant_id, "gateway_{$this->gateway_name}_key");
    }

    public function setWebhookSecret(string $secret): void
    {
        $this->webhook_secret_encrypted = VaultEncryption::encrypt($secret, $this->tenant_id, "gateway_{$this->gateway_name}_sec");
    }

    public function getDecryptedWebhookSecret(): ?string
    {
        if (empty($this->webhook_secret_encrypted)) {
            return null;
        }

        return VaultEncryption::decrypt($this->webhook_secret_encrypted, $this->tenant_id, "gateway_{$this->gateway_name}_sec");
    }
}
