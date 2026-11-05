<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Scopes;

use App\Core\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * Implements Fail-Closed Zero-Trust policy:
     * If TenantContext is active, restrict by tenant_id.
     * If TenantContext is empty, strictly deny all rows (whereRaw 1 = 0).
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (TenantContext::check()) {
            $builder->where($model->qualifyColumn('tenant_id'), '=', TenantContext::id());
        } else {
            // Fail-closed policy: deny all rows when tenant context is missing
            $builder->whereRaw('1 = 0');
        }
    }
}
