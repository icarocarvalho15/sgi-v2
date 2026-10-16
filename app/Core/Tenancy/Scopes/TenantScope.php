<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Scopes;

use App\Core\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TenantScope implements Scope
{
    /**
     * Aplica o escopo de isolamento multi-tenant de modo estritamente Fail-Closed.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        // Se estiver explicitamente em Landlord Bypass (ex: consolidação ou rotas Landlord autorizadas)
        if ($context->isLandlordBypass()) {
            return;
        }

        $tenantId = $context->id();

        if ($tenantId !== null && $tenantId !== '') {
            $builder->where($model->qualifyColumn('tenant_id'), '=', $tenantId);

            return;
        }

        // Fail-Closed: Sem tenant ativo e sem bypass explícito, força 0 linhas
        $builder->whereRaw('1 = 0');
    }
}
