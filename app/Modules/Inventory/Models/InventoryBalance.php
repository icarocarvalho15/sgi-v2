<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saldo de Estoque por Insumo/Produto e Almoxarifado.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $item_id
 * @property string $warehouse_id
 * @property string $quantity_on_hand
 * @property string $quantity_reserved
 * @property string $average_cost
 * @property string $total_value
 * @property \Illuminate\Support\Carbon|null $last_movement_at
 */
class InventoryBalance extends TenantModel
{
    protected $table = 'inventory_balances';

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'quantity_on_hand',
        'quantity_reserved',
        'average_cost',
        'total_value',
        'last_movement_at',
    ];

    protected $casts = [
        'quantity_on_hand' => 'string',
        'quantity_reserved' => 'string',
        'average_cost' => 'string',
        'total_value' => 'string',
        'last_movement_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'quantity_on_hand' => '0.0000',
        'quantity_reserved' => '0.0000',
        'average_cost' => '0.0000',
        'total_value' => '0.0000',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    /**
     * Quantidade disponível (saldo físico - reservado).
     */
    public function getAvailableQuantityAttribute(): string
    {
        return bcsub($this->quantity_on_hand ?? '0.0000', $this->quantity_reserved ?? '0.0000', 4);
    }
}
