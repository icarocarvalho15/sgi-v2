<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Services;

use App\Modules\Finance\Actions\SettleFinancialPaymentAction;
use App\Modules\Finance\DTOs\SettleFinancialPaymentDTO;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Fiscal\Enums\PaymentGateway;
use App\Modules\Fiscal\Enums\WebhookEventStatus;
use App\Modules\Fiscal\Gateways\PaymentGatewayManager;
use App\Modules\Fiscal\Models\PaymentGatewaySetting;
use App\Modules\Fiscal\Models\WebhookEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class WebhookService
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly SettleFinancialPaymentAction $settleAction,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, event_id: string, settled: bool}
     */
    public function handle(
        PaymentGateway $gateway,
        string $rawPayload,
        string $signatureHeader,
        array $payload
    ): array {
        /** @var PaymentGatewaySetting $setting */
        $setting = PaymentGatewaySetting::where('gateway', $gateway->value)
            ->where('is_active', true)
            ->firstOrFail();

        $adapter = $this->gatewayManager->resolve($setting);
        $secret = (string) $setting->getDecryptedWebhookSecret();

        // 1. Validação Criptográfica de Assinatura HMAC
        if ($secret !== '' && ! $adapter->verifyWebhookSignature($rawPayload, $signatureHeader, $secret)) {
            throw new AccessDeniedHttpException("Assinatura HMAC inválida para o gateway {$gateway->value}.");
        }

        $parsed = $adapter->parseWebhookPayload($payload);

        // 2. Trava de Idempotência: Se o evento já foi processado anteriormente, retorna sucesso sem duplicidade
        $existing = WebhookEvent::where('gateway', $gateway->value)
            ->where('external_event_id', $parsed->externalEventId)
            ->first();

        if ($existing) {
            return [
                'status' => 'already_processed',
                'event_id' => $existing->id,
                'settled' => false,
            ];
        }

        // 3. Processamento Atômico do Webhook
        return DB::transaction(function () use ($gateway, $parsed, $payload): array {
            $settled = false;
            $entryId = $parsed->financialEntryId;

            // Se for evento de confirmação de pagamento e possuir título vinculado
            if ($parsed->isPaid && $entryId !== null) {
                $entry = FinancialEntry::where('id', $entryId)->lockForUpdate()->first();

                if ($entry && bccomp((string) $entry->balance_amount, '0.00', 2) > 0) {
                    /** @var FinancialAccount|null $account */
                    $account = FinancialAccount::where('is_active', true)
                        ->orderByDesc('is_default')
                        ->first();

                    if ($account) {
                        $this->settleAction->execute(new SettleFinancialPaymentDTO(
                            financialEntryId: $entry->id,
                            financialAccountId: $account->id,
                            paymentMethod: PaymentMethod::Pix,
                            paymentDate: $parsed->paymentDate,
                            amount: bccomp($parsed->amount, (string) $entry->balance_amount, 2) > 0 ? (string) $entry->balance_amount : $parsed->amount,
                            notes: "Baixa automática via Webhook {$gateway->value} (Evento {$parsed->externalEventId})",
                        ));
                        $settled = true;
                    }
                }
            }

            $event = WebhookEvent::create([
                'gateway' => $gateway,
                'external_event_id' => $parsed->externalEventId,
                'event_type' => $parsed->eventType,
                'financial_entry_id' => $entryId,
                'payload' => $payload,
                'status' => WebhookEventStatus::Processed,
                'processed_at' => Carbon::now(),
            ]);

            return [
                'status' => 'processed',
                'event_id' => $event->id,
                'settled' => $settled,
            ];
        });
    }
}
