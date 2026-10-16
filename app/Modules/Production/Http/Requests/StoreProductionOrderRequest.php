<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Core\Tenancy\Rules\TenantExists;
use App\Modules\Production\Enums\ProductionOrderPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProductionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'uuid', new TenantExists('items', 'id')],
            'bom_id' => ['required', 'uuid', new TenantExists('boms', 'id')],
            'warehouse_id' => ['required', 'uuid', new TenantExists('warehouses', 'id')],
            'materials_warehouse_id' => ['required', 'uuid', new TenantExists('warehouses', 'id')],
            'planned_quantity' => ['required', 'numeric', 'gt:0'],
            'priority' => ['sometimes', Rule::enum(ProductionOrderPriority::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
