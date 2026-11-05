<?php

declare(strict_types=1);

namespace App\Modules\CRM\Requests;

use App\Core\Security\Rules\CpfCnpjRule;
use App\Core\Security\Rules\TenantUnique;
use Illuminate\Foundation\Http\FormRequest;

class StorePartnerRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:customer,supplier,both'],
            'person_type' => ['required', 'string', 'in:individual,company'],
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document_number' => [
                'required',
                'string',
                'max:20',
                new CpfCnpjRule(),
                new TenantUnique('partners', 'document_number'),
            ],
            'state_registration' => ['nullable', 'string', 'max:30'],
            'municipal_registration' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'is_tax_exempt' => ['boolean'],
            'notes' => ['nullable', 'string'],
            'addresses' => ['nullable', 'array'],
            'addresses.*.type' => ['required_with:addresses', 'string', 'in:main,billing,shipping'],
            'addresses.*.postal_code' => ['required_with:addresses', 'string', 'max:10'],
            'addresses.*.street' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.number' => ['required_with:addresses', 'string', 'max:20'],
            'addresses.*.complement' => ['nullable', 'string', 'max:100'],
            'addresses.*.neighborhood' => ['required_with:addresses', 'string', 'max:100'],
            'addresses.*.city' => ['required_with:addresses', 'string', 'max:100'],
            'addresses.*.state' => ['required_with:addresses', 'string', 'size:2'],
            'addresses.*.is_default' => ['boolean'],
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
