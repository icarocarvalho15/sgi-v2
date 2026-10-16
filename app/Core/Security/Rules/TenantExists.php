<?php

declare(strict_types=1);

namespace App\Core\Security\Rules;

use App\Core\Tenancy\TenantContext;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Regra de validação para garantir que uma chave estrangeira pertence
 * estritamente ao Tenant do contexto ativo, prevenindo enumeração cross-tenant.
 */
final class TenantExists implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $column = 'id',
        private readonly ?string $tenantColumn = 'tenant_id'
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            $fail("Não foi possível validar o campo {$attribute} sem um contexto de empresa ativo.");

            return;
        }

        $query = DB::table($this->table)
            ->where($this->column, $value);

        if ($this->tenantColumn !== null) {
            $query->where($this->tenantColumn, $tenantId);
        }

        if (! $query->exists()) {
            $fail("O registro selecionado para {$attribute} é inválido ou não pertence à sua empresa.");
        }
    }
}
