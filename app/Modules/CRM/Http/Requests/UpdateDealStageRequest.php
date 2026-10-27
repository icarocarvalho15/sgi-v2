<?php

declare(strict_types=1);

namespace App\Modules\CRM\Http\Requests;

use App\Core\Security\Rules\TenantExists;
use App\Modules\CRM\Enums\DealStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDealStageRequest extends FormRequest
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
            'stage_id' => ['required', 'uuid', new TenantExists('crm_deal_stages', 'id')],
            'status' => ['nullable', Rule::enum(DealStatus::class)],
            'lost_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
