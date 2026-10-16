<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Core\Security\Rules\TenantExists;
use App\Modules\Inventory\DTOs\StockAdjustmentDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StockAdjustmentRequest extends FormRequest
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
            'warehouse_id' => ['required', 'uuid', new TenantExists('warehouses', 'id')],
            'item_id' => ['required', 'uuid', new TenantExists('items', 'id')],
            'direction' => ['required', 'string', Rule::in(['IN', 'OUT'])],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'justification' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function toDTO(): StockAdjustmentDTO
    {
        $validated = $this->validated();

        return new StockAdjustmentDTO(
            warehouseId: (string) $validated['warehouse_id'],
            itemId: (string) $validated['item_id'],
            direction: (string) $validated['direction'],
            quantity: number_format((float) $validated['quantity'], 4, '.', ''),
            justification: (string) $validated['justification'],
            unitCost: isset($validated['unit_cost']) && $validated['unit_cost'] !== ''
                ? number_format((float) $validated['unit_cost'], 4, '.', '')
                : null,
            userId: auth()->id()
        );
    }
}
