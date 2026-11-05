<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Models;

use App\Core\Security\VaultEncryption;
use App\Core\Tenancy\Models\TenantModel;

/**
 * Configurações Fiscais e Certificado Digital A1 do Tenant.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $cnpj
 * @property string|null $state_registration
 * @property string|null $municipal_registration
 * @property string $tax_regime
 * @property string $environment
 * @property int $next_nfe_number
 * @property int $nfe_series
 * @property int $next_nfse_number
 * @property int $nfse_series
 * @property string|null $default_ncm
 * @property string|null $default_cfop
 * @property string|null $certificate_a1_encrypted
 * @property string|null $certificate_password_encrypted
 * @property \Illuminate\Support\Carbon|null $certificate_expires_at
 * @property string $fiscal_provider
 * @property string|null $fiscal_token_encrypted
 */
class TenantFiscalSetting extends TenantModel
{
    protected $table = 'tenant_fiscal_settings';

    protected $fillable = [
        'cnpj',
        'state_registration',
        'municipal_registration',
        'tax_regime',
        'environment',
        'next_nfe_number',
        'nfe_series',
        'next_nfse_number',
        'nfse_series',
        'default_ncm',
        'default_cfop',
        'certificate_a1_encrypted',
        'certificate_password_encrypted',
        'certificate_expires_at',
        'fiscal_provider',
        'fiscal_token_encrypted',
    ];

    protected $hidden = [
        'certificate_a1_encrypted',
        'certificate_password_encrypted',
        'fiscal_token_encrypted',
    ];

    protected $casts = [
        'next_nfe_number' => 'integer',
        'nfe_series' => 'integer',
        'next_nfse_number' => 'integer',
        'nfse_series' => 'integer',
        'certificate_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Define o Certificado A1 encriptando no cofre (AES-256-GCM + HKDF).
     */
    public function setCertificateA1(string $pfxBinaryContent, string $password): void
    {
        $this->certificate_a1_encrypted = VaultEncryption::encrypt($pfxBinaryContent, $this->tenant_id, 'fiscal_a1');
        $this->certificate_password_encrypted = VaultEncryption::encrypt($password, $this->tenant_id, 'fiscal_a1_pwd');
    }

    /**
     * Retorna o Certificado A1 binário descriptografado em memória.
     */
    public function getDecryptedCertificateA1(): ?string
    {
        if (empty($this->certificate_a1_encrypted)) {
            return null;
        }

        return VaultEncryption::decrypt($this->certificate_a1_encrypted, $this->tenant_id, 'fiscal_a1');
    }

    /**
     * Retorna a senha do Certificado A1 descriptografada em memória.
     */
    public function getDecryptedCertificatePassword(): ?string
    {
        if (empty($this->certificate_password_encrypted)) {
            return null;
        }

        return VaultEncryption::decrypt($this->certificate_password_encrypted, $this->tenant_id, 'fiscal_a1_pwd');
    }
}
