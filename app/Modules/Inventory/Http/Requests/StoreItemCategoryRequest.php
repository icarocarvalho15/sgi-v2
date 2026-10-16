<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Core\Security\Rules\TenantExists;
use App\Core\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreItemCategoryRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('item_categories', 'name')->where('tenant_id', $tenantId),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'uuid', new TenantExists('item_categories', 'id')],
        ];
    }
}
