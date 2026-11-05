<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\BankAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Models\FinancialPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinancialPaymentService
{
    /**
     * Efetua a baixa (total ou parcial) de um título financeiro com lock pessimista.
     *
     * @param array{
     *     amount: string|numeric,
     *     bank_account_id: string,
     *     payment_method: string,
     *     payment_date?: string|null,
     *     discount_amount?: string|numeric|null,
     *     interest_fine_amount?: string|numeric|null,
     *     user_id?: string|null,
     *     external_transaction_id?: string|null,
     *     notes?: string|null
     * } $data
     */
    public function recordPayment(FinancialEntry $entry, array $data): FinancialPayment
    {
        return DB::transaction(function () use ($entry, $data) {
            $paymentAmount = bcadd((string) $data['amount'], '0', 4);
            $discount = bcadd((string) ($data['discount_amount'] ?? '0'), '0', 4);
            $interestFine = bcadd((string) ($data['interest_fine_amount'] ?? '0'), '0', 4);

            if (bccomp($paymentAmount, '0.0000', 4) <= 0) {
                throw new InvalidArgumentException('O valor da baixa deve ser estritamente maior que zero.');
            }

            // Lock pessimista no título
            /** @var FinancialEntry $lockedEntry */
            $lockedEntry = FinancialEntry::where('id', $entry->id)->lockForUpdate()->firstOrFail();

            if ($lockedEntry->status === 'paid') {
                throw new InvalidArgumentException('Este título financeiro já foi liquidado integralmente.');
            }

            // Lock pessimista na conta bancária
            /** @var BankAccount $bankAccount */
            $bankAccount = BankAccount::where('id', $data['bank_account_id'])->lockForUpdate()->firstOrFail();

            $paymentDate = Carbon::parse($data['payment_date'] ?? now());

            // Recalcula valor líquido se houver acréscimo de juros/multas ou desconto nesta baixa
            $currentNet = $lockedEntry->net_amount;
            if (bccomp($interestFine, '0.0000', 4) > 0) {
                $currentNet = bcadd($currentNet, $interestFine, 4);
            }
            if (bccomp($discount, '0.0000', 4) > 0) {
                $currentNet = bcsub($currentNet, $discount, 4);
            }

            $newPaid = bcadd((string) $lockedEntry->paid_amount, $paymentAmount, 4);
            $remainingDue = bcsub($currentNet, $newPaid, 4);

            $isFullyPaid = bccomp($remainingDue, '0.0000', 4) <= 0;

            $lockedEntry->update([
                'net_amount' => $currentNet,
                'discount_amount' => bcadd((string) $lockedEntry->discount_amount, $discount, 4),
                'interest_fine_amount' => bcadd((string) $lockedEntry->interest_fine_amount, $interestFine, 4),
                'paid_amount' => $newPaid,
                'balance_due' => $isFullyPaid ? '0.0000' : $remainingDue,
                'status' => $isFullyPaid ? 'paid' : 'partial',
                'payment_date' => $isFullyPaid ? $paymentDate->toDateString() : $lockedEntry->payment_date,
            ]);

            // Atualiza saldo bancário
            if ($lockedEntry->type === 'receivable') {
                $newBankBalance = bcadd((string) $bankAccount->current_balance, $paymentAmount, 4);
            } else {
                $newBankBalance = bcsub((string) $bankAccount->current_balance, $paymentAmount, 4);
            }

            $bankAccount->update(['current_balance' => $newBankBalance]);

            // Registra o pagamento
            return FinancialPayment::create([
                'financial_entry_id' => $lockedEntry->id,
                'bank_account_id' => $bankAccount->id,
                'payment_method' => $data['payment_method'],
                'amount' => $paymentAmount,
                'discount_amount' => $discount,
                'interest_fine_amount' => $interestFine,
                'payment_date' => $paymentDate->toDateString(),
                'user_id' => $data['user_id'] ?? null,
                'external_transaction_id' => $data['external_transaction_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }
}
