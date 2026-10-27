<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\DTOs\SettleFinancialPaymentDTO;
use App\Modules\Finance\Enums\EntryStatus;
use App\Modules\Finance\Enums\EntryType;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Models\FinancialPayment;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SettleFinancialPaymentAction
{
    public function execute(SettleFinancialPaymentDTO $dto): FinancialPayment
    {
        return DB::transaction(function () use ($dto): FinancialPayment {
            /** @var FinancialEntry $entry */
            $entry = FinancialEntry::where('id', $dto->financialEntryId)->lockForUpdate()->firstOrFail();

            /** @var FinancialAccount $account */
            $account = FinancialAccount::where('id', $dto->financialAccountId)->lockForUpdate()->firstOrFail();

            if ($entry->status === EntryStatus::Cancelled) {
                throw new DomainException("O título {$entry->document_number} está cancelado e não pode receber baixas.");
            }

            if ($entry->status === EntryStatus::Paid || bccomp((string) $entry->balance_amount, '0.00', 2) <= 0) {
                throw new DomainException("O título {$entry->document_number} já está totalmente liquidado.");
            }

            $amount = (string) $dto->amount;
            if (bccomp($amount, '0.00', 2) <= 0) {
                throw new InvalidArgumentException('O valor principal da baixa deve ser maior que zero.');
            }

            if (bccomp($amount, (string) $entry->balance_amount, 2) > 0) {
                throw new DomainException("O valor da baixa (R$ {$amount}) excede o saldo pendente (R$ {$entry->balance_amount}) do título.");
            }

            $interest = (string) ($dto->interestPaid ?: '0.00');
            $fine = (string) ($dto->finePaid ?: '0.00');
            $discount = (string) ($dto->discountApplied ?: '0.00');

            // Valor Líquido = Principal + Juros + Multa - Desconto
            $additionals = bcadd(bcadd($amount, $interest, 2), $fine, 2);
            $netAmount = bcsub($additionals, $discount, 2);

            // 1. Atualiza saldos do título
            $newBalance = bcsub((string) $entry->balance_amount, $amount, 2);
            $newPaid = bcadd((string) $entry->paid_amount, $amount, 2);
            $newInterest = bcadd((string) $entry->interest_amount, $interest, 2);
            $newFine = bcadd((string) $entry->fine_amount, $fine, 2);
            $newDiscount = bcadd((string) $entry->discount_amount, $discount, 2);

            $status = bccomp($newBalance, '0.00', 2) <= 0 ? EntryStatus::Paid : EntryStatus::PartiallyPaid;

            $entry->update([
                'balance_amount' => $newBalance,
                'paid_amount' => $newPaid,
                'interest_amount' => $newInterest,
                'fine_amount' => $newFine,
                'discount_amount' => $newDiscount,
                'status' => $status,
            ]);

            // 2. Atualiza saldo da conta bancária
            if ($entry->entry_type === EntryType::Receivable) {
                $newAccountBalance = bcadd((string) $account->current_balance, $netAmount, 2);
            } else {
                $newAccountBalance = bcsub((string) $account->current_balance, $netAmount, 2);
            }

            $account->update([
                'current_balance' => $newAccountBalance,
            ]);

            // 3. Cria o registro imutável do pagamento
            return FinancialPayment::create([
                'financial_entry_id' => $entry->id,
                'financial_account_id' => $account->id,
                'payment_method' => $dto->paymentMethod,
                'payment_date' => $dto->paymentDate,
                'amount' => $amount,
                'interest_paid' => $interest,
                'fine_paid' => $fine,
                'discount_applied' => $discount,
                'net_amount' => $netAmount,
                'notes' => $dto->notes,
            ]);
        });
    }
}
