<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Controllers;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\BankAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Services\FinancialPaymentService;
use App\Modules\Integrations\Models\PaymentGatewayConfig;
use App\Modules\Integrations\Models\WebhookEvent;
use App\Modules\Integrations\Payments\PaymentGatewayFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class WebhookController extends Controller
{
    public function handle(string $provider, Request $request, FinancialPaymentService $paymentService): JsonResponse
    {
        // 1. Resolver Tenant através do header, query param ou payload
        $tenantId = $request->header('X-Tenant-ID')
            ?? $request->query('tenant_id')
            ?? $request->input('tenant_id')
            ?? $request->input('data.tenant_id');

        /** @var Tenant|null $tenant */
        $tenant = $tenantId ? Tenant::find($tenantId) : Tenant::first();

        if (! $tenant) {
            return response()->json(['error' => 'Tenant não identificado para o webhook.'], 400);
        }

        // 2. Obter o webhook_secret configurado para o Tenant
        /** @var PaymentGatewayConfig|null $gatewayConfig */
        $gatewayConfig = PaymentGatewayConfig::where('tenant_id', $tenant->id)
            ->where('gateway_name', $provider)
            ->first();

        $webhookSecret = $gatewayConfig?->getDecryptedWebhookSecret()
            ?? (string) config("services.{$provider}.webhook_secret", 'secret_test_token');

        // 3. Validar assinatura criptográfica com hash_equals()
        $adapter = PaymentGatewayFactory::make($tenant, $provider);

        try {
            $webhookData = $adapter->verifyWebhookSignature($request, $webhookSecret);
        } catch (UnauthorizedHttpException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }

        // 4. Verificação de Idempotência Estrita
        $existingEvent = WebhookEvent::where('provider', $provider)
            ->where('external_event_id', $webhookData->eventId)
            ->first();

        if ($existingEvent) {
            if ($existingEvent->status === 'processed') {
                return response()->json(['message' => 'Evento já processado anteriormente (idempotente).'], 200);
            }
        } else {
            $existingEvent = WebhookEvent::create([
                'provider' => $provider,
                'external_event_id' => $webhookData->eventId,
                'tenant_id' => $tenant->id,
                'event_type' => $webhookData->eventType,
                'status' => 'pending',
                'payload' => $webhookData->rawPayload,
            ]);
        }

        // 5. Processar baixa financeira no contexto do Tenant
        try {
            TenantContext::set($tenant);

            if ($webhookData->status === 'paid' && $webhookData->externalChargeId) {
                // Localizar o título a receber vinculado ao ID externo
                $entry = FinancialEntry::where(function ($q) use ($webhookData) {
                    if (\Illuminate\Support\Str::isUuid($webhookData->externalChargeId)) {
                        $q->where('id', $webhookData->externalChargeId)
                            ->orWhere('reference_id', $webhookData->externalChargeId);
                    } else {
                        $q->where('entry_number', $webhookData->externalChargeId);
                    }
                })->where('type', 'receivable')->first();

                if ($entry && $entry->status !== 'paid') {
                    // Localizar ou criar conta bancária padrão
                    $bankAccount = BankAccount::where('is_default', true)->first() ?? BankAccount::first();
                    if (! $bankAccount) {
                        $bankAccount = BankAccount::create([
                            'name' => 'Conta Gateway ' . ucfirst($provider),
                            'type' => 'gateway',
                            'is_default' => true,
                        ]);
                    }

                    $paymentAmount = $webhookData->amount ?: $entry->balance_due;

                    $paymentService->recordPayment($entry, [
                        'amount' => $paymentAmount,
                        'bank_account_id' => $bankAccount->id,
                        'payment_method' => str_contains(strtolower($webhookData->eventType), 'pix') ? 'pix' : 'boleto',
                        'external_transaction_id' => $webhookData->externalChargeId,
                        'notes' => "Baixa automática via Webhook {$provider} ({$webhookData->eventType})",
                    ]);
                }
            }

            $existingEvent->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            return response()->json(['status' => 'success', 'message' => 'Webhook processado com sucesso.']);
        } catch (Throwable $e) {
            $existingEvent->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
