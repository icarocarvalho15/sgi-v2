<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $production_order_id
 * @property string $item_id
 * @property string $unit_of_measure_id
 * @property string $planned_quantity
 * @property string $consumed_quantity
 * @property string $unit_cost
 * @property string $total_cost
 */
final class ProductionOrderMaterial extends TenantModel
{
    protected $table = 'production_order_materials';

    protected $fillable = [
        'production_order_id',
        'item_id',
        'unit_of_measure_id',
        'planned_quantity',
        'consumed_quantity',
        'unit_cost',
        'total_cost',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:4',
            'consumed_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_of_measure_id');
    }
}
