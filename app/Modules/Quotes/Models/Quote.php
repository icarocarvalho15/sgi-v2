<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\CRM\Models\Partner;
use App\Modules\SalesOrders\Models\SalesOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Quote represents technical quotes for Printing and Promotional Gifts.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $quote_number
 * @property string $customer_id
 * @property string $status_id
 * @property string $title
 * @property string|null $notes
 * @property string $subtotal
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $freight_amount
 * @property string $total_amount
 * @property string $estimated_cost
 * @property string $target_margin_percent
 * @property int $production_lead_time_days
 * @property string|null $valid_until
 * @property array|null $metadata
 */
class Quote extends TenantModel
{
    use SoftDeletes;

    protected $table = 'quotes';

    protected $fillable = [
        'quote_number',
        'customer_id',
        'status_id',
        'title',
        'notes',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'freight_amount',
        'total_amount',
        'estimated_cost',
        'target_margin_percent',
        'production_lead_time_days',
        'valid_until',
        'metadata',
    ];

    protected $attributes = [
        'subtotal' => '0.0000',
        'discount_amount' => '0.0000',
        'tax_amount' => '0.0000',
        'freight_amount' => '0.0000',
        'total_amount' => '0.0000',
        'estimated_cost' => '0.0000',
        'target_margin_percent' => '0.0000',
        'production_lead_time_days' => 5,
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'freight_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'estimated_cost' => 'decimal:4',
            'target_margin_percent' => 'decimal:4',
            'production_lead_time_days' => 'integer',
            'valid_until' => 'date',
            'metadata' => 'array',
        ];
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
        return $this->hasMany(QuoteItem::class, 'quote_id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'quote_id');
    }
}
