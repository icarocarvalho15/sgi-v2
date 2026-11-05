<?php

declare(strict_types=1);

namespace App\Core\Security\Rules;

use App\Core\Tenancy\TenantContext;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

final class TenantExists implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $column = 'id',
        private readonly ?Closure $additionalConstraint = null
    ) {}

    /**
     * Run the validation rule.
     * Ensures foreign keys are strictly checked against the CURRENT tenant only.
     * Prevents cross-tenant IDOR and enumeration vulnerabilities.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! TenantContext::check()) {
            $fail('Contexto de Tenant não definido para validação de segurança.');
            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        $query = DB::table($this->table)
            ->where('tenant_id', TenantContext::id())
            ->where($this->column, $value);

        if ($this->additionalConstraint !== null) {
            ($this->additionalConstraint)($query);
        }

        if (! $query->exists()) {
            $fail("O registro selecionado em {$attribute} é inválido ou pertence a outra organização.");
        }
    }
}
