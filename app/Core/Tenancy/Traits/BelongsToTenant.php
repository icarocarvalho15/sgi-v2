<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Traits;

use App\Core\Tenancy\Exceptions\CrossTenantTamperingException;
use App\Core\Tenancy\Exceptions\TenantContextMissingException;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Scopes\TenantScope;
use App\Core\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the trait to attach global scope and model lifecycle hooks.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(static function (self $model): void {
            $context = app(TenantContext::class);

            if (empty($model->tenant_id)) {
                $tenantId = $context->id();

                if ($tenantId === null || $tenantId === '') {
                    if (! $context->isLandlordBypass()) {
                        throw new TenantContextMissingException(
                            'Tentativa de criação do modelo '.static::class.' sem contexto de tenant ativo.'
                        );
                    }
                } else {
                    $model->tenant_id = $tenantId;
                }
            }
        });

        static::updating(static function (self $model): void {
            if ($model->isDirty('tenant_id')) {
                throw new CrossTenantTamperingException(
                    'Violação de segurança multi-tenant: alteração de tenant_id não é permitida no modelo '.static::class.'.'
                );
            }
        });
    }

    /**
     * Relacionamento com o Tenant proprietário do registro.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
