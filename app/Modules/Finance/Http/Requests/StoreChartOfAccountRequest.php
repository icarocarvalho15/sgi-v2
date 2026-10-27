<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Core\Security\Rules\TenantExists;
use App\Modules\Finance\Enums\AccountCategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreChartOfAccountRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AccountCategoryType::class)],
            'parent_id' => ['nullable', 'uuid', new TenantExists('chart_of_accounts', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
