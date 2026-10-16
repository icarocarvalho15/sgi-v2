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
 * @property string $bom_id
 * @property string $item_id
 * @property string $unit_of_measure_id
 * @property string $quantity
 * @property string $scrap_rate_percentage
 * @property string $unit_cost
 * @property string $total_cost
 * @property string|null $notes
 */
final class BomItem extends TenantModel
{
    protected $table = 'bom_items';

    protected $fillable = [
        'bom_id',
        'item_id',
        'unit_of_measure_id',
        'quantity',
        'scrap_rate_percentage',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'scrap_rate_percentage' => 'decimal:2',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
        ];
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
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
