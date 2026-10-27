<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Core\Security\Rules\TenantExists;
use App\Modules\Finance\Enums\EntryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFinancialEntryRequest extends FormRequest
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
            'entry_type' => ['required', Rule::enum(EntryType::class)],
            'chart_of_account_id' => ['required', 'uuid', new TenantExists('chart_of_accounts', 'id')],
            'partner_id' => ['nullable', 'uuid', new TenantExists('partners', 'id')],
            'cost_center_id' => ['nullable', 'uuid', new TenantExists('cost_centers', 'id')],
            'sales_order_id' => ['nullable', 'uuid', new TenantExists('sales_orders', 'id')],
            'financial_account_id' => ['nullable', 'uuid', new TenantExists('financial_accounts', 'id')],
            'document_number' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'gt:0'],
            'installments_count' => ['nullable', 'integer', 'min:1'],
            'first_due_date' => ['required', 'date'],
            'issue_date' => ['nullable', 'date'],
            'competence_date' => ['nullable', 'date'],
            'interval_days' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
