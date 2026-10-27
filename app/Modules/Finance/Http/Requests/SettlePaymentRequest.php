<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Core\Security\Rules\TenantExists;
use App\Modules\Finance\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SettlePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'financial_account_id' => ['required', 'uuid', new TenantExists('financial_accounts', 'id')],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'interest_paid' => ['nullable', 'numeric', 'min:0'],
            'fine_paid' => ['nullable', 'numeric', 'min:0'],
            'discount_applied' => ['nullable', 'numeric', 'min:0'],
            'document_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
