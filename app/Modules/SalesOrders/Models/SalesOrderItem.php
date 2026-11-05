<?php

declare(strict_types=1);

namespace App\Modules\SalesOrders\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\Quotes\Models\QuoteItem;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SalesOrderItem.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $sales_order_id
 * @property string|null $quote_item_id
 * @property string $name
 * @property int $quantity
 * @property string $unit_price
 * @property string $total_price
 */
class SalesOrderItem extends TenantModel
{
    protected $table = 'sales_order_items';

    protected $fillable = [
        'sales_order_id',
        'quote_item_id',
        'name',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:4',
            'total_price' => 'decimal:4',
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
}
