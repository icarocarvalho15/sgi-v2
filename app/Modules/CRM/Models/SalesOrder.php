<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\CRM\Enums\SalesOrderStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $order_number
 * @property string $partner_id
 * @property string|null $quote_id
 * @property string|null $deal_id
 * @property SalesOrderStatus $status
 * @property string $total_amount
 * @property CarbonInterface|null $delivery_date
 * @property string|null $payment_terms
 * @property string|null $notes
 */
final class SalesOrder extends TenantModel
{
    protected $table = 'sales_orders';

    protected $fillable = [
        'order_number',
        'partner_id',
        'quote_id',
        'deal_id',
        'status',
        'total_amount',
        'delivery_date',
        'payment_terms',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SalesOrderStatus::class,
            'total_amount' => 'decimal:2',
            'delivery_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    /**
     * @return BelongsTo<CrmDeal, $this>
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(CrmDeal::class, 'deal_id');
    }

    /**
     * @return HasMany<SalesOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id');
    }
}
