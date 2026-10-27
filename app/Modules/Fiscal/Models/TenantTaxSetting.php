<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Models;

use App\Core\Security\VaultEncryption;
use App\Core\Tenancy\Models\TenantModel;
use App\Modules\Fiscal\Enums\FiscalEnvironment;
use App\Modules\Fiscal\Enums\TaxRegime;

final class TenantTaxSetting extends TenantModel
{
    protected $table = 'tenant_tax_settings';

    protected $fillable = [
        'certificate_path',
        'certificate_password',
        'certificate_expires_at',
        'environment',
        'tax_regime',
        'state_registration',
        'municipal_registration',
        'nfe_series',
        'next_nfe_number',
        'default_cfop',
        'default_csosn',
        'default_cst',
        'default_iss_rate',
    ];

    protected $casts = [
        'environment' => FiscalEnvironment::class,
        'tax_regime' => TaxRegime::class,
        'certificate_expires_at' => 'datetime',
        'nfe_series' => 'integer',
        'next_nfe_number' => 'integer',
        'default_iss_rate' => 'string',
    ];

    public function setEncryptedCertificatePassword(string $plainPassword): void
    {
        $vault = app(VaultEncryption::class);
        $this->certificate_password = $vault->encrypt($plainPassword, $this->tenant_id, 'fiscal_cert');
    }

    public function getDecryptedCertificatePassword(): ?string
    {
        if (empty($this->certificate_password)) {
            return null;
        }

        $vault = app(VaultEncryption::class);

        return $vault->decrypt($this->certificate_password, $this->tenant_id, 'fiscal_cert');
    }
}
