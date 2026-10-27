<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\DTOs\CreateFinancialEntryDTO;
use App\Modules\Finance\Enums\EntryStatus;
use App\Modules\Finance\Models\FinancialEntry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class FinancialEntryService
{
    /**
     * @return Collection<int, FinancialEntry>
     */
    public function createWithInstallments(CreateFinancialEntryDTO $dto): Collection
    {
        return DB::transaction(function () use ($dto): Collection {
            $count = max(1, $dto->installmentsCount);
            $total = bccomp((string) $dto->totalAmount, '0.00', 2) > 0 ? (string) $dto->totalAmount : '0.00';

            // Valor base por parcela
            $baseInstallment = bcdiv($total, (string) $count, 2);
            // Diferença de centavos
            $distributedSum = bcmul($baseInstallment, (string) $count, 2);
            $centsRemainder = bcsub($total, $distributedSum, 2);

            $issueDate = $dto->issueDate ?: Carbon::now()->toDateString();
            $competenceDate = $dto->competenceDate ?: $issueDate;
            $firstDue = Carbon::parse($dto->firstDueDate);

            $createdEntries = new Collection;

            for ($i = 1; $i <= $count; $i++) {
                // Adiciona os centavos restantes na primeira parcela
                $installmentAmount = $i === 1 ? bcadd($baseInstallment, $centsRemainder, 2) : $baseInstallment;

                $docNumber = $count > 1
                    ? "{$dto->documentNumber}-{$i}/{$count}"
                    : $dto->documentNumber;

                $dueDate = $firstDue->copy()->addDays(($i - 1) * $dto->intervalDays)->toDateString();

                $entry = FinancialEntry::create([
                    'entry_type' => $dto->entryType,
                    'partner_id' => $dto->partnerId,
                    'chart_of_account_id' => $dto->chartOfAccountId,
                    'cost_center_id' => $dto->costCenterId,
                    'sales_order_id' => $dto->salesOrderId,
                    'document_number' => $docNumber,
                    'title' => $count > 1 ? "{$dto->title} ({$i}/{$count})" : $dto->title,
                    'installment_number' => $i,
                    'total_installments' => $count,
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                    'competence_date' => $competenceDate,
                    'original_amount' => $installmentAmount,
                    'balance_amount' => $installmentAmount,
                    'paid_amount' => '0.00',
                    'interest_amount' => '0.00',
                    'fine_amount' => '0.00',
                    'discount_amount' => '0.00',
                    'status' => EntryStatus::Pending,
                    'notes' => $dto->notes,
                ]);

                $createdEntries->push($entry);
            }

            return $createdEntries;
        });
    }
}
