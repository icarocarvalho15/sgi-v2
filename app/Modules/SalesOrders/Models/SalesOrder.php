<?php

declare(strict_types=1);

namespace App\Modules\SalesOrders\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\CRM\Models\Partner;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SalesOrder generated via 1-click quote conversion or direct sale.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $order_number
 * @property string|null $quote_id
 * @property string $customer_id
 * @property string $status_id
 * @property string $total_amount
 * @property string|null $delivery_date
 * @property string|null $payment_terms
 * @property string|null $notes
 */
class SalesOrder extends TenantModel
{
    use SoftDeletes;

    protected $table = 'sales_orders';

    protected $fillable = [
        'order_number',
        'quote_id',
        'customer_id',
        'status_id',
        'total_amount',
        'delivery_date',
        'payment_terms',
        'notes',
    ];

    protected $attributes = [
        'total_amount' => '0.0000',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:4',
            'delivery_date' => 'date',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TenantStatus::class, 'status_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'sales_order_id');
    }
}
