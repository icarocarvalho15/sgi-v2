<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Controllers;

use App\Core\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Integrations\Models\PaymentGatewayConfig;
use App\Modules\Integrations\Models\TenantFiscalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FiscalSettingsController extends Controller
{
    public function index(): Response
    {
        $tenantId = TenantContext::id();

        $fiscalSettings = TenantFiscalSetting::first();
        $gateways = PaymentGatewayConfig::where('tenant_id', $tenantId)->get();

        return Inertia::render('Tenant/Settings/Fiscal/Index', [
            'fiscalSettings' => $fiscalSettings,
            'gateways' => $gateways,
            'hasCertificate' => ! empty($fiscalSettings?->certificate_a1_encrypted),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cnpj' => ['required', 'string', 'max:20'],
            'state_registration' => ['nullable', 'string', 'max:30'],
            'municipal_registration' => ['nullable', 'string', 'max:30'],
            'tax_regime' => ['required', 'in:simples_nacional,lucro_presumido,lucro_real'],
            'environment' => ['required', 'in:sandbox,production'],
            'default_ncm' => ['nullable', 'string', 'max:20'],
            'default_cfop' => ['nullable', 'string', 'max:10'],
            'certificate_password' => ['nullable', 'string'],
            'gateway_name' => ['nullable', 'string', 'in:asaas,mercadopago,stripe'],
            'gateway_api_key' => ['nullable', 'string'],
            'gateway_webhook_secret' => ['nullable', 'string'],
        ]);

        /** @var TenantFiscalSetting $settings */
        $settings = TenantFiscalSetting::firstOrNew([]);
        $settings->fill([
            'cnpj' => $validated['cnpj'],
            'state_registration' => $validated['state_registration'] ?? null,
            'municipal_registration' => $validated['municipal_registration'] ?? null,
            'tax_regime' => $validated['tax_regime'],
            'environment' => $validated['environment'],
            'default_ncm' => $validated['default_ncm'] ?? '49111090',
            'default_cfop' => $validated['default_cfop'] ?? '5101',
        ]);

        // Se enviou arquivo de certificado PFX
        if ($request->hasFile('certificate_file') && ! empty($validated['certificate_password'])) {
            $file = $request->file('certificate_file');
            $pfxContent = file_get_contents($file->getRealPath());
            $settings->setCertificateA1($pfxContent, $validated['certificate_password']);
            $settings->certificate_expires_at = now()->addYear();
        }

        $settings->save();

        // Configuração de Gateway se fornecido
        if (! empty($validated['gateway_name'])) {
            /** @var PaymentGatewayConfig $gwConfig */
            $gwConfig = PaymentGatewayConfig::firstOrNew(['gateway_name' => $validated['gateway_name']]);
            $gwConfig->is_active = true;
            $gwConfig->mode = $validated['environment'];

            if (! empty($validated['gateway_api_key'])) {
                $gwConfig->setApiKey($validated['gateway_api_key']);
            }
            if (! empty($validated['gateway_webhook_secret'])) {
                $gwConfig->setWebhookSecret($validated['gateway_webhook_secret']);
            }
            $gwConfig->save();
        }

        return redirect()->route('tenant.settings.fiscal.index')
            ->with('success', 'Configurações fiscais e de pagamento salvas com sucesso no cofre seguro!');
    }
}
