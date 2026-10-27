<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Requests;

use App\Modules\Fiscal\Enums\FiscalEnvironment;
use App\Modules\Fiscal\Enums\TaxRegime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTaxSettingRequest extends FormRequest
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
            'environment' => ['required', Rule::enum(FiscalEnvironment::class)],
            'tax_regime' => ['required', Rule::enum(TaxRegime::class)],
            'state_registration' => ['nullable', 'string', 'max:30'],
            'municipal_registration' => ['nullable', 'string', 'max:30'],
            'nfe_series' => ['required', 'integer', 'min:1'],
            'next_nfe_number' => ['required', 'integer', 'min:1'],
            'default_cfop' => ['required', 'string', 'max:10'],
            'default_csosn' => ['nullable', 'string', 'max:10'],
            'default_cst' => ['nullable', 'string', 'max:10'],
            'default_iss_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'certificate_file' => ['nullable', 'file', 'max:10240'],
            'certificate_password' => ['nullable', 'string', 'max:255'],
        ];
    }
}
