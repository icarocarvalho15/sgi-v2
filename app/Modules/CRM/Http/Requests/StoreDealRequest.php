<?php

declare(strict_types=1);

namespace App\Modules\CRM\Http\Requests;

use App\Core\Security\Rules\TenantExists;
use App\Modules\CRM\Enums\DealPriority;
use App\Modules\CRM\Enums\DealStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDealRequest extends FormRequest
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
            'pipeline_id' => ['required', 'uuid', new TenantExists('crm_pipelines', 'id')],
            'stage_id' => ['required', 'uuid', new TenantExists('crm_deal_stages', 'id')],
            'partner_id' => ['required', 'uuid', new TenantExists('partners', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(DealStatus::class)],
            'priority' => ['nullable', Rule::enum(DealPriority::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
