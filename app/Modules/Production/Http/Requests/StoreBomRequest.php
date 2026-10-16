<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Core\Tenancy\Rules\TenantExists;
use Illuminate\Foundation\Http\FormRequest;

final class StoreBomRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50'],
            'version' => ['sometimes', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'batch_size' => ['required', 'numeric', 'gt:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'uuid', new TenantExists('items', 'id')],
            'items.*.unit_of_measure_id' => ['required', 'uuid', new TenantExists('units_of_measure', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.scrap_rate_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],

            'routing_steps' => ['sometimes', 'array'],
            'routing_steps.*.work_center_id' => ['required_with:routing_steps', 'uuid', new TenantExists('work_centers', 'id')],
            'routing_steps.*.sequence' => ['required_with:routing_steps', 'integer', 'min:1'],
            'routing_steps.*.name' => ['required_with:routing_steps', 'string', 'max:255'],
            'routing_steps.*.description' => ['nullable', 'string'],
            'routing_steps.*.setup_time_minutes' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'routing_steps.*.run_time_minutes_per_unit' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
