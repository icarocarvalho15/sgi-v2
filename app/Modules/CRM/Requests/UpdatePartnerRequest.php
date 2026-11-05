<?php

declare(strict_types=1);

namespace App\Modules\CRM\Requests;

use App\Core\Security\Rules\CpfCnpjRule;
use App\Core\Security\Rules\TenantUnique;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePartnerRequest extends FormRequest
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
        $partnerId = (string) $this->route('partner')?->id ?? (string) $this->route('partner');

        return [
            'type' => ['sometimes', 'required', 'string', 'in:customer,supplier,both'],
            'person_type' => ['sometimes', 'required', 'string', 'in:individual,company'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document_number' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                new CpfCnpjRule(),
                new TenantUnique('partners', 'document_number', $partnerId),
            ],
            'state_registration' => ['nullable', 'string', 'max:30'],
            'municipal_registration' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'is_tax_exempt' => ['boolean'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('document_number')) {
            $this->merge([
                'document_number' => preg_replace('/\D/', '', (string) $this->input('document_number')),
            ]);
        }
    }
}
