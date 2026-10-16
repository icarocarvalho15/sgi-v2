<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreWorkCenterRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'hourly_cost' => ['required', 'numeric', 'min:0'],
            'capacity_per_day_hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
