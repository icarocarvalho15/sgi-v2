<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Core\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreUnitOfMeasureRequest extends FormRequest
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

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('units_of_measure', 'code')->where('tenant_id', $tenantId),
            ],
            'symbol' => ['required', 'string', 'max:10'],
            'type' => ['required', 'string', Rule::in(['unit', 'weight', 'volume', 'length', 'area'])],
            'is_active' => ['boolean'],
        ];
    }
}
