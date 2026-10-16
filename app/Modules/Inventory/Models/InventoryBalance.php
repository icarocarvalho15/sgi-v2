<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Tenancy\Models\TenantModel;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Snapshot derivado do razão de estoque, mantido atomicamente pelo KardexLedgerService.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $warehouse_id
 * @property string $item_id
 * @property string $quantity_on_hand
 * @property string $quantity_reserved
 * @property string $average_cost
 * @property CarbonInterface|null $last_movement_at
 */
final class InventoryBalance extends TenantModel
{
    protected $table = 'inventory_balances';

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'quantity_on_hand',
        'quantity_reserved',
        'average_cost',
        'last_movement_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:4',
            'quantity_reserved' => 'decimal:4',
            'average_cost' => 'decimal:4',
            'last_movement_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
