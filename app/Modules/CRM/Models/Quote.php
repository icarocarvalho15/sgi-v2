<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\CRM\Enums\QuoteStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $quote_number
 * @property string $partner_id
 * @property string|null $deal_id
 * @property QuoteStatus $status
 * @property string $total_materials_cost
 * @property string $total_operations_cost
 * @property string $markup_percentage
 * @property string $margin_percentage
 * @property string $final_total
 * @property int $lead_time_days
 * @property CarbonInterface|null $valid_until
 * @property CarbonInterface|null $approved_at
 * @property CarbonInterface|null $converted_at
 * @property string|null $notes
 */
final class Quote extends TenantModel
{
    protected $table = 'quotes';

    protected $fillable = [
        'quote_number',
        'partner_id',
        'deal_id',
        'status',
        'total_materials_cost',
        'total_operations_cost',
        'markup_percentage',
        'margin_percentage',
        'final_total',
        'lead_time_days',
        'valid_until',
        'approved_at',
        'converted_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'total_materials_cost' => 'decimal:4',
            'total_operations_cost' => 'decimal:4',
            'markup_percentage' => 'decimal:2',
            'margin_percentage' => 'decimal:2',
            'final_total' => 'decimal:2',
            'lead_time_days' => 'integer',
            'valid_until' => 'date',
            'approved_at' => 'datetime',
            'converted_at' => 'datetime',
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
     * @return BelongsTo<CrmDeal, $this>
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(CrmDeal::class, 'deal_id');
    }

    /**
     * @return HasMany<QuoteItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class, 'quote_id');
    }

    /**
     * @return HasOne<SalesOrder, $this>
     */
    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class, 'quote_id');
    }
}
