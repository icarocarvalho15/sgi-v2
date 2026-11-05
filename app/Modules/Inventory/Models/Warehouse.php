<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Depósito / Almoxarifado.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $name
 * @property string|null $location
 * @property bool $is_default
 * @property bool $is_active
 */
class Warehouse extends TenantModel
{
    use SoftDeletes;

    protected $table = 'warehouses';

    protected $fillable = [
        'code',
        'name',
        'location',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'is_default' => false,
        'is_active' => true,
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'warehouse_id', 'id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(KardexMovement::class, 'warehouse_id', 'id');
    }
}
