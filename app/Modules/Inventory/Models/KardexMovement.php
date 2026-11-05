<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Razão Imutável de Estoque (Kardex Movement).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $item_id
 * @property string $warehouse_id
 * @property string $movement_type
 * @property string $direction
 * @property string $quantity
 * @property string $unit_cost
 * @property string $total_cost
 * @property string $balance_quantity_after
 * @property string $balance_average_cost_after
 * @property string $balance_total_value_after
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property string|null $user_id
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 */
class KardexMovement extends TenantModel
{
    public $timestamps = false;

    protected $table = 'kardex_movements';

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'movement_type',
        'direction',
        'quantity',
        'unit_cost',
        'total_cost',
        'balance_quantity_after',
        'balance_average_cost_after',
        'balance_total_value_after',
        'reference_type',
        'reference_id',
        'user_id',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'quantity' => 'string',
        'unit_cost' => 'string',
        'total_cost' => 'string',
        'balance_quantity_after' => 'string',
        'balance_average_cost_after' => 'string',
        'balance_total_value_after' => 'string',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::updating(function () {
            throw new LogicException('Kardex movements are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new LogicException('Kardex movements are immutable and cannot be deleted.');
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
}
