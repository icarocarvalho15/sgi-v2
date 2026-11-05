<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Insumo ou Produto Gráfico / Brinde.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $category_id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property string $unit_of_measure
 * @property string $min_stock
 * @property string $max_stock
 * @property string $standard_cost
 * @property bool $is_active
 * @property array|null $specifications
 */
class Item extends TenantModel
{
    use SoftDeletes;

    protected $table = 'items';

    protected $fillable = [
        'category_id',
        'code',
        'name',
        'type',
        'unit_of_measure',
        'min_stock',
        'max_stock',
        'standard_cost',
        'is_active',
        'specifications',
    ];

    protected $casts = [
        'min_stock' => 'string',
        'max_stock' => 'string',
        'standard_cost' => 'string',
        'is_active' => 'boolean',
        'specifications' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'type' => 'raw_material',
        'unit_of_measure' => 'UN',
        'min_stock' => '0.0000',
        'max_stock' => '0.0000',
        'standard_cost' => '0.0000',
        'is_active' => true,
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id', 'id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'item_id', 'id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(KardexMovement::class, 'item_id', 'id');
    }
}
