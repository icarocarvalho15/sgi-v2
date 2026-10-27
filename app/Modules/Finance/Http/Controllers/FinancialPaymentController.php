<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Actions\ReverseFinancialPaymentAction;
use App\Modules\Finance\Actions\SettleFinancialPaymentAction;
use App\Modules\Finance\DTOs\SettleFinancialPaymentDTO;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Http\Requests\ReversePaymentRequest;
use App\Modules\Finance\Http\Requests\SettlePaymentRequest;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Models\FinancialPayment;
use Illuminate\Http\RedirectResponse;

final class FinancialPaymentController extends Controller
{
    public function __construct(
        private readonly SettleFinancialPaymentAction $settleAction,
        private readonly ReverseFinancialPaymentAction $reverseAction,
    ) {}

    public function settle(SettlePaymentRequest $request, FinancialEntry $entry): RedirectResponse
    {
        $data = $request->validated();

        $dto = new SettleFinancialPaymentDTO(
            financialEntryId: $entry->id,
            financialAccountId: $data['financial_account_id'],
            paymentMethod: PaymentMethod::from($data['payment_method']),
            paymentDate: $data['payment_date'],
            amount: (string) $data['amount'],
            interestPaid: (string) ($data['interest_paid'] ?? '0.00'),
            finePaid: (string) ($data['fine_paid'] ?? '0.00'),
            discountApplied: (string) ($data['discount_applied'] ?? '0.00'),
            notes: $data['notes'] ?? null,
        );

        $this->settleAction->execute($dto);

        return back()->with('success', 'Baixa registrada e saldo conciliado com sucesso!');
    }

    public function reverse(ReversePaymentRequest $request, FinancialPayment $payment): RedirectResponse
    {
        $reason = $request->string('reason')->toString();

        $this->reverseAction->execute($payment->id, $reason);

        return back()->with('success', 'Pagamento estornado e saldos restaurados com sucesso!');
    }
}
