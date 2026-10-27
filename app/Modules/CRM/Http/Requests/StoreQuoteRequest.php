<?php

declare(strict_types=1);

namespace App\Modules\CRM\Http\Requests;

use App\Core\Security\Rules\TenantExists;
use Illuminate\Foundation\Http\FormRequest;

final class StoreQuoteRequest extends FormRequest
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
            'deal_id' => ['nullable', 'uuid', new TenantExists('crm_deals', 'id')],
            'markup_percentage' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:1'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'uuid', new TenantExists('items', 'id')],
            'items.*.bom_id' => ['nullable', 'uuid', new TenantExists('boms', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.markup_percentage' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
