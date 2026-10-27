<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\EntryStatus;
use App\Modules\Finance\Enums\EntryType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Models\FinancialPayment;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ReverseFinancialPaymentAction
{
    public function execute(string $paymentId, ?string $reason = null): FinancialPayment
    {
        return DB::transaction(function () use ($paymentId, $reason): FinancialPayment {
            /** @var FinancialPayment $payment */
            $payment = FinancialPayment::where('id', $paymentId)->lockForUpdate()->firstOrFail();

            if ($payment->is_reversed) {
                throw new DomainException('Este pagamento já foi estornado anteriormente.');
            }

            /** @var FinancialEntry $entry */
            $entry = FinancialEntry::where('id', $payment->financial_entry_id)->lockForUpdate()->firstOrFail();

            /** @var FinancialAccount $account */
            $account = FinancialAccount::where('id', $payment->financial_account_id)->lockForUpdate()->firstOrFail();

            // 1. Restaura saldos do título
            $restoredBalance = bcadd((string) $entry->balance_amount, (string) $payment->amount, 2);
            $restoredPaid = bcsub((string) $entry->paid_amount, (string) $payment->amount, 2);
            $restoredInterest = bcsub((string) $entry->interest_amount, (string) $payment->interest_paid, 2);
            $restoredFine = bcsub((string) $entry->fine_amount, (string) $payment->fine_paid, 2);
            $restoredDiscount = bcsub((string) $entry->discount_amount, (string) $payment->discount_applied, 2);

            $status = bccomp($restoredPaid, '0.00', 2) > 0 ? EntryStatus::PartiallyPaid : EntryStatus::Pending;

            $entry->update([
                'balance_amount' => $restoredBalance,
                'paid_amount' => $restoredPaid,
                'interest_amount' => $restoredInterest,
                'fine_amount' => $restoredFine,
                'discount_amount' => $restoredDiscount,
                'status' => $status,
            ]);

            // 2. Estorna saldo na conta bancária
            if ($entry->entry_type === EntryType::Receivable) {
                $newAccountBalance = bcsub((string) $account->current_balance, (string) $payment->net_amount, 2);
            } else {
                $newAccountBalance = bcadd((string) $account->current_balance, (string) $payment->net_amount, 2);
            }

            $account->update([
                'current_balance' => $newAccountBalance,
            ]);

            // 3. Marca o estorno no registro do pagamento
            $payment->update([
                'is_reversed' => true,
                'reversal_reason' => $reason ?: 'Estorno de pagamento solicitado pelo operador.',
                'reversed_at' => Carbon::now(),
            ]);

            return $payment->fresh();
        });
    }
}
