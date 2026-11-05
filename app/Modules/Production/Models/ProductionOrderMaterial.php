<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Material allocated / reserved for a specific Production Order.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $production_order_id
 * @property string|null $bom_item_id
 * @property string $name
 * @property string $item_type
 * @property string $planned_quantity
 * @property string $consumed_quantity
 * @property string $unit_of_measure
 * @property string $status
 */
class ProductionOrderMaterial extends TenantModel
{
    protected $table = 'production_order_materials';

    protected $fillable = [
        'production_order_id',
        'bom_item_id',
        'name',
        'item_type',
        'planned_quantity',
        'consumed_quantity',
        'unit_of_measure',
        'status',
    ];

    protected $attributes = [
        'consumed_quantity' => '0.0000',
        'unit_of_measure' => 'UN',
        'status' => 'reserved',
    ];

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:4',
            'consumed_quantity' => 'decimal:4',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function bomItem(): BelongsTo
    {
        return $this->belongsTo(BomItem::class, 'bom_item_id');
    }
}
