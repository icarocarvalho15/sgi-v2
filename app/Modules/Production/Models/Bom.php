<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\Inventory\Models\Item;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $item_id
 * @property string $code
 * @property int $version
 * @property string|null $description
 * @property string $batch_size
 * @property string $total_estimated_cost
 * @property bool $is_active
 * @property bool $is_default
 */
final class Bom extends TenantModel
{
    protected $table = 'boms';

    protected $fillable = [
        'item_id',
        'code',
        'version',
        'description',
        'batch_size',
        'total_estimated_cost',
        'is_active',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'batch_size' => 'decimal:4',
            'total_estimated_cost' => 'decimal:4',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class, 'bom_id');
    }

    public function routingSteps(): HasMany
    {
        return $this->hasMany(RoutingStep::class, 'bom_id')->orderBy('sequence');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'bom_id');
    }
}
