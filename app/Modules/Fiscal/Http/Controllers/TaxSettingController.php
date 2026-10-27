<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Controllers;

use App\Core\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Fiscal\Http\Requests\UpdateTaxSettingRequest;
use App\Modules\Fiscal\Models\TenantTaxSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TaxSettingController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = app(TenantContext::class)->id();

        /** @var TenantTaxSetting $taxSetting */
        $taxSetting = TenantTaxSetting::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'environment' => 'HOMOLOGATION',
                'tax_regime' => 'SIMPLES_NACIONAL',
                'nfe_series' => 1,
                'next_nfe_number' => 1,
                'default_cfop' => '5101',
                'default_csosn' => '102',
                'default_iss_rate' => '0.00',
            ]
        );

        return Inertia::render('Fiscal/Settings/TaxSettings', [
            'taxSetting' => $taxSetting,
            'hasCertificate' => ! empty($taxSetting->certificate_path),
        ]);
    }

    public function update(UpdateTaxSettingRequest $request): RedirectResponse
    {
        $tenantId = app(TenantContext::class)->id();

        /** @var TenantTaxSetting $taxSetting */
        $taxSetting = TenantTaxSetting::firstOrCreate(['tenant_id' => $tenantId]);

        $data = $request->validated();
        $certFile = $request->file('certificate_file');
        $certPassword = $data['certificate_password'] ?? null;

        unset($data['certificate_file'], $data['certificate_password']);

        if ($certFile) {
            $path = $certFile->storeAs("certificates/{$tenantId}", 'cert_a1.pfx', 'local');
            $data['certificate_path'] = $path;
        }

        $taxSetting->fill($data);

        if (! empty($certPassword)) {
            $taxSetting->setEncryptedCertificatePassword($certPassword);
        }

        $taxSetting->save();

        return redirect()->route('tenant.fiscal.settings.tax')
            ->with('success', 'Parâmetros fiscais e Certificado A1 atualizados com sucesso no cofre criptográfico!');
    }
}
