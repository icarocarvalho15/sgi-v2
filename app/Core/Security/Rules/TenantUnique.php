<?php

declare(strict_types=1);

namespace App\Core\Security\Rules;

use App\Core\Tenancy\TenantContext;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

final class TenantUnique implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $column,
        private readonly ?string $ignoreId = null,
        private readonly string $idColumn = 'id'
    ) {}

    /**
     * Run the validation rule.
     * Ensures uniqueness scoped strictly within the current tenant.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! TenantContext::check()) {
            $fail('Contexto de Tenant não definido para validação de unicidade.');
            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        $query = DB::table($this->table)
            ->where('tenant_id', TenantContext::id())
            ->where($this->column, $value);

        if ($this->ignoreId !== null) {
            $query->where($this->idColumn, '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail("O valor informado para {$attribute} já está em uso na sua empresa.");
        }
    }
}
