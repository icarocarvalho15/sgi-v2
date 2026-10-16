<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $sku
 * @property string $name
 * @property string|null $description
 * @property string $unit_of_measure_id
 * @property string|null $category_id
 * @property string|null $default_warehouse_id
 * @property bool $is_raw_material
 * @property bool $is_manufactured
 * @property bool $is_sellable
 * @property bool $is_service
 * @property bool $allow_negative_stock
 * @property string $min_stock
 * @property string|null $max_stock
 * @property string $average_cost
 * @property string $last_cost
 * @property string $sale_price
 * @property bool $is_active
 */
final class Item extends TenantModel
{
    protected $table = 'items';

    protected $fillable = [
        'sku',
        'name',
        'description',
        'unit_of_measure_id',
        'category_id',
        'default_warehouse_id',
        'is_raw_material',
        'is_manufactured',
        'is_sellable',
        'is_service',
        'allow_negative_stock',
        'min_stock',
        'max_stock',
        'average_cost',
        'last_cost',
        'sale_price',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_raw_material' => 'boolean',
            'is_manufactured' => 'boolean',
            'is_sellable' => 'boolean',
            'is_service' => 'boolean',
            'allow_negative_stock' => 'boolean',
            'is_active' => 'boolean',
            'min_stock' => 'decimal:4',
            'max_stock' => 'decimal:4',
            'average_cost' => 'decimal:4',
            'last_cost' => 'decimal:4',
            'sale_price' => 'decimal:2',
        ];
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_of_measure_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'item_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(KardexMovement::class, 'item_id');
    }

    /**
     * Retorna o saldo físico consolidado somando todos os armazéns.
     */
    public function getTotalStockOnHandAttribute(): string
    {
        return (string) $this->balances()->sum('quantity_on_hand');
    }
}
