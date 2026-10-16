<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RecordProductionRequest extends FormRequest
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
            'produced_quantity' => ['required', 'numeric', 'gt:0'],
            'scrapped_quantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
