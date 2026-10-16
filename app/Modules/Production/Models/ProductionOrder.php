<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Production\Enums\ProductionOrderPriority;
use App\Modules\Production\Enums\ProductionOrderStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $order_number
 * @property string $item_id
 * @property string $bom_id
 * @property string $warehouse_id
 * @property string $materials_warehouse_id
 * @property string $planned_quantity
 * @property string $produced_quantity
 * @property string $scrapped_quantity
 * @property ProductionOrderStatus $status
 * @property ProductionOrderPriority $priority
 * @property CarbonInterface|null $start_date
 * @property CarbonInterface|null $due_date
 * @property CarbonInterface|null $actual_start_at
 * @property CarbonInterface|null $actual_end_at
 * @property string|null $notes
 */
final class ProductionOrder extends TenantModel
{
    protected $table = 'production_orders';

    protected $fillable = [
        'order_number',
        'item_id',
        'bom_id',
        'warehouse_id',
        'materials_warehouse_id',
        'planned_quantity',
        'produced_quantity',
        'scrapped_quantity',
        'status',
        'priority',
        'start_date',
        'due_date',
        'actual_start_at',
        'actual_end_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:4',
            'produced_quantity' => 'decimal:4',
            'scrapped_quantity' => 'decimal:4',
            'status' => ProductionOrderStatus::class,
            'priority' => ProductionOrderPriority::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'actual_start_at' => 'datetime',
            'actual_end_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function materialsWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'materials_warehouse_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionOrderMaterial::class, 'production_order_id');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(ProductionOrderOperation::class, 'production_order_id')->orderBy('sequence');
    }
}
