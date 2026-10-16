<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property bool $is_default
 */
final class Warehouse extends TenantModel
{
    protected $table = 'warehouses';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'warehouse_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(KardexMovement::class, 'warehouse_id');
    }
}
