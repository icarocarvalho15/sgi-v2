<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\FinancialEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinancialEntryService
{
    /**
     * Cria títulos financeiros com suporte a parcelamento e precisão Zero Float (bcmath).
     *
     * @param array{
     *     type: 'receivable'|'payable',
     *     partner_id?: string|null,
     *     chart_of_account_id?: string|null,
     *     cost_center_id?: string|null,
     *     bank_account_id?: string|null,
     *     description: string,
     *     original_amount: string|numeric,
     *     issue_date?: string|null,
     *     first_due_date?: string|null,
     *     competence_date?: string|null,
     *     total_installments?: int,
     *     reference_type?: string|null,
     *     reference_id?: string|null,
     *     notes?: string|null
     * } $data
     * @return array<FinancialEntry>
     */
    public function createEntriesWithInstallments(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $totalAmount = bcadd((string) $data['original_amount'], '0', 4);
            if (bccomp($totalAmount, '0.0000', 4) <= 0) {
                throw new InvalidArgumentException('O valor do título deve ser estritamente maior que zero.');
            }

            $installmentsCount = (int) ($data['total_installments'] ?? 1);
            if ($installmentsCount < 1) {
                $installmentsCount = 1;
            }

            $issueDate = Carbon::parse($data['issue_date'] ?? now());
            $firstDueDate = Carbon::parse($data['first_due_date'] ?? now()->addDays(30));
            $competenceDate = Carbon::parse($data['competence_date'] ?? $issueDate);

            $baseAmount = bcdiv($totalAmount, (string) $installmentsCount, 4);
            $accumulated = bcmul($baseAmount, (string) $installmentsCount, 4);
            $diff = bcsub($totalAmount, $accumulated, 4);

            $prefix = $data['type'] === 'receivable' ? 'REC' : 'PAG';
            $baseNumber = $prefix . '-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $created = [];

            for ($i = 1; $i <= $installmentsCount; $i++) {
                // Ajusta centavos residuais na primeira parcela
                $installmentAmount = ($i === 1 && bccomp($diff, '0.0000', 4) !== 0)
                    ? bcadd($baseAmount, $diff, 4)
                    : $baseAmount;

                $dueDate = $firstDueDate->copy()->addMonthsNoOverflow($i - 1);
                $entryNumber = $installmentsCount > 1
                    ? sprintf('%s/%02d', $baseNumber, $i)
                    : $baseNumber;

                $entry = FinancialEntry::create([
                    'type' => $data['type'],
                    'entry_number' => $entryNumber,
                    'partner_id' => $data['partner_id'] ?? null,
                    'chart_of_account_id' => $data['chart_of_account_id'] ?? null,
                    'cost_center_id' => $data['cost_center_id'] ?? null,
                    'bank_account_id' => $data['bank_account_id'] ?? null,
                    'status' => 'pending',
                    'description' => $installmentsCount > 1
                        ? "{$data['description']} (Parcela {$i}/{$installmentsCount})"
                        : $data['description'],
                    'original_amount' => $installmentAmount,
                    'discount_amount' => '0.0000',
                    'interest_fine_amount' => '0.0000',
                    'net_amount' => $installmentAmount,
                    'paid_amount' => '0.0000',
                    'balance_due' => $installmentAmount,
                    'issue_date' => $issueDate->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'competence_date' => $competenceDate->toDateString(),
                    'installment_number' => $i,
                    'total_installments' => $installmentsCount,
                    'reference_type' => $data['reference_type'] ?? null,
                    'reference_id' => $data['reference_id'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                $created[] = $entry;
            }

            return $created;
        });
    }
}
