<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Requests;

use App\Core\Security\Rules\TenantExists;
use App\Modules\Fiscal\Enums\FiscalInvoiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EmitFiscalInvoiceRequest extends FormRequest
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
            'partner_id' => ['required', 'uuid', new TenantExists('partners', 'id')],
            'sales_order_id' => ['nullable', 'uuid', new TenantExists('sales_orders', 'id')],
            'invoice_type' => ['required', Rule::enum(FiscalInvoiceType::class)],
            'total_amount' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
