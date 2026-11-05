<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\Quotes\Models\QuoteItem;
use App\Modules\SalesOrders\Models\SalesOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ProductionOrder (OP) for shop floor operations.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $order_number
 * @property string|null $sales_order_id
 * @property string|null $quote_item_id
 * @property string|null $bom_id
 * @property string $status_id
 * @property string $product_name
 * @property int $target_quantity
 * @property int $produced_quantity
 * @property string|null $start_date
 * @property string|null $due_date
 * @property array|null $technical_notes
 */
class ProductionOrder extends TenantModel
{
    use SoftDeletes;

    protected $table = 'production_orders';

    protected $fillable = [
        'order_number',
        'sales_order_id',
        'quote_item_id',
        'bom_id',
        'status_id',
        'product_name',
        'target_quantity',
        'produced_quantity',
        'start_date',
        'due_date',
        'technical_notes',
    ];

    protected $attributes = [
        'produced_quantity' => 0,
    ];

    protected function casts(): array
    {
        return [
            'target_quantity' => 'integer',
            'produced_quantity' => 'integer',
            'start_date' => 'date',
            'due_date' => 'date',
            'technical_notes' => 'array',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function quoteItem(): BelongsTo
    {
        return $this->belongsTo(QuoteItem::class, 'quote_item_id');
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TenantStatus::class, 'status_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionOrderMaterial::class, 'production_order_id');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(ProductionTimeLog::class, 'production_order_id');
    }
}
