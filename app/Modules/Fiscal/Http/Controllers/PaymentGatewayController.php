<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Controllers;

use App\Core\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Fiscal\Http\Requests\StorePaymentGatewayRequest;
use App\Modules\Fiscal\Models\PaymentGatewaySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PaymentGatewayController extends Controller
{
    public function index(Request $request): Response
    {
        $gateways = PaymentGatewaySetting::orderBy('gateway')->get();

        return Inertia::render('Fiscal/Settings/Gateways', [
            'gateways' => $gateways,
        ]);
    }

    public function store(StorePaymentGatewayRequest $request): RedirectResponse
    {
        $tenantId = app(TenantContext::class)->id();
        $data = $request->validated();

        $apiKey = $data['api_key'];
        $webhookSecret = $data['webhook_secret'] ?? null;
        unset($data['api_key'], $data['webhook_secret']);

        /** @var PaymentGatewaySetting $setting */
        $setting = PaymentGatewaySetting::firstOrNew([
            'tenant_id' => $tenantId,
            'gateway' => $data['gateway'],
        ]);

        $setting->fill($data);
        $setting->setEncryptedApiKey($apiKey);
        $setting->setEncryptedWebhookSecret($webhookSecret);
        $setting->save();

        return redirect()->route('tenant.fiscal.settings.gateways')
            ->with('success', "Configurações do gateway {$data['gateway']} salvas com sucesso!");
    }
}
