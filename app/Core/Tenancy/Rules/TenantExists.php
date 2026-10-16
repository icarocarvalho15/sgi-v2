<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Rules;

use App\Core\Tenancy\TenantContext;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

final class TenantExists implements ValidationRule
{
    /**
     * @param  string  $table  Nome da tabela a ser validada
     * @param  string  $column  Nome da coluna identificadora (padrão: 'id')
     */
    public function __construct(
        private readonly string $table,
        private readonly string $column = 'id',
    ) {}

    /**
     * Executa a regra de validação com garantia de escopo de tenant.
     *
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $context = app(TenantContext::class);
        $tenantId = $context->id();

        if ($tenantId === null || $tenantId === '') {
            $fail('Não foi possível validar o campo :attribute devido à ausência de tenant ativo.');

            return;
        }

        $exists = DB::table($this->table)
            ->where($this->column, $value)
            ->where('tenant_id', $tenantId)
            ->exists();

        if (! $exists) {
            $fail('O registro selecionado em :attribute é inválido ou não pertence à sua organização.');
        }
    }
}
