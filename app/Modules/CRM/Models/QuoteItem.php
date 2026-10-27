<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\Inventory\Models\Item;
use App\Modules\Production\Models\Bom;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $quote_id
 * @property string $item_id
 * @property string|null $bom_id
 * @property string $quantity
 * @property string $unit_cost
 * @property string $markup_percentage
 * @property string $unit_price
 * @property string $total_price
 * @property int $lead_time_days
 * @property string|null $notes
 */
final class QuoteItem extends TenantModel
{
    protected $table = 'quote_items';

    protected $fillable = [
        'quote_id',
        'item_id',
        'bom_id',
        'quantity',
        'unit_cost',
        'markup_percentage',
        'unit_price',
        'total_price',
        'lead_time_days',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'markup_percentage' => 'decimal:2',
            'unit_price' => 'decimal:4',
            'total_price' => 'decimal:2',
            'lead_time_days' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * @return BelongsTo<Bom, $this>
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }
}
