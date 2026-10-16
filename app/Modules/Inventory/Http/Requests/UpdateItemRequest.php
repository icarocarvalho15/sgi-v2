<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Core\Security\Rules\TenantExists;
use App\Core\Tenancy\TenantContext;
use App\Modules\Inventory\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateItemRequest extends FormRequest
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
        $tenantId = app(TenantContext::class)->id();
        $itemId = $this->route('item') instanceof Item
            ? $this->route('item')->id
            : (string) $this->route('item');

        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('items', 'sku')->where('tenant_id', $tenantId)->ignore($itemId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_of_measure_id' => ['required', 'uuid', new TenantExists('units_of_measure', 'id')],
            'category_id' => ['nullable', 'uuid', new TenantExists('item_categories', 'id')],
            'default_warehouse_id' => ['nullable', 'uuid', new TenantExists('warehouses', 'id')],
            'is_raw_material' => ['boolean'],
            'is_manufactured' => ['boolean'],
            'is_sellable' => ['boolean'],
            'is_service' => ['boolean'],
            'allow_negative_stock' => ['boolean'],
            'min_stock' => ['numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
