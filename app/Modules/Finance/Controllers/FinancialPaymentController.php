<?php

declare(strict_types=1);

namespace App\Modules\Finance\Controllers;

use App\Core\Security\Rules\TenantExists;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Services\FinancialPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class FinancialPaymentController extends Controller
{
    public function store(Request $request, FinancialPaymentService $paymentService): RedirectResponse
    {
        $validated = $request->validate([
            'financial_entry_id' => ['required', 'uuid', new TenantExists('financial_entries')],
            'bank_account_id' => ['required', 'uuid', new TenantExists('bank_accounts')],
            'payment_method' => ['required', 'in:pix,boleto,credit_card,bank_transfer,cash'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'interest_fine_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var FinancialEntry $entry */
        $entry = FinancialEntry::findOrFail($validated['financial_entry_id']);

        try {
            $paymentService->recordPayment($entry, [
                'amount' => (string) $validated['amount'],
                'bank_account_id' => $validated['bank_account_id'],
                'payment_method' => $validated['payment_method'],
                'discount_amount' => isset($validated['discount_amount']) ? (string) $validated['discount_amount'] : '0.0000',
                'interest_fine_amount' => isset($validated['interest_fine_amount']) ? (string) $validated['interest_fine_amount'] : '0.0000',
                'payment_date' => $validated['payment_date'] ?? now()->toDateString(),
                'user_id' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            return back()->with('success', 'Baixa financeira efetuada com sucesso!');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
    }
}
