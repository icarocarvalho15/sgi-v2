<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models;

use App\Core\Tenancy\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Abstract base model for all operational domain models in SGI v2.0.
 * Enforces Double-Lock isolation:
 * - HasUuids: string/UUID primary keys.
 * - BelongsToTenant: Global TenantScope + Fail-Closed injection + CrossTenantTampering prevention.
 * - Guarded tenant_id: blocks mass assignment.
 */
abstract class TenantModel extends Model
{
    use HasUuids, BelongsToTenant;

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Columns that are guarded against mass-assignment across all tenant models.
     * tenant_id is strictly guarded to prevent injection via fill() / create($request->all()).
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'tenant_id',
    ];
}
