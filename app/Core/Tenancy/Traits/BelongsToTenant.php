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
     * Boot the BelongsToTenant trait for the model.
     */
    public static function bootBelongsToTenant(): void
    {
        // 1. Apply global scope (Double Lock layer 1)
        static::addGlobalScope(new TenantScope());

        // 2. Automatically inject and validate tenant_id on creation
        static::creating(function ($model): void {
            if (empty($model->tenant_id)) {
                if (! TenantContext::check()) {
                    throw new TenantContextMissingException(
                        sprintf('Falha ao criar [%s]: nenhum TenantContext ativo.', static::class)
                    );
                }
                $model->tenant_id = TenantContext::id();
            } else {
                // If tenant_id was explicitly provided, verify it matches active context to prevent spoofing
                if (TenantContext::check() && (string) $model->tenant_id !== (string) TenantContext::id()) {
                    throw new CrossTenantTamperingException(
                        sprintf('Tentativa de spoofing em [%s]: tenant_id informado difere do TenantContext ativo.', static::class)
                    );
                }
            }
        });

        // 3. Prevent any alteration of tenant_id during updates
        static::updating(function ($model): void {
            if ($model->isDirty('tenant_id')) {
                throw new CrossTenantTamperingException(
                    sprintf('Violação em [%s]: alteração da coluna tenant_id é estritamente proibida.', static::class)
                );
            }
        });
    }

    /**
     * Model relationship to the Tenant root entity.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
