<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Technical Quote Item: print specs, CTP, gift customization, finishing operations.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $quote_id
 * @property string $product_type
 * @property string $name
 * @property int $quantity
 * @property string|null $format_open
 * @property string|null $format_closed
 * @property int $pages_count
 * @property string|null $substrate_type
 * @property int $colors_front
 * @property int $colors_back
 * @property int $ctp_plates_count
 * @property string|null $customization_process
 * @property array|null $finishing_operations
 * @property string $unit_cost
 * @property string $markup_multiplier
 * @property string $unit_price
 * @property string $total_price
 */
class QuoteItem extends TenantModel
{
    protected $table = 'quote_items';

    protected $fillable = [
        'quote_id',
        'product_type',
        'name',
        'quantity',
        'format_open',
        'format_closed',
        'pages_count',
        'substrate_type',
        'colors_front',
        'colors_back',
        'ctp_plates_count',
        'customization_process',
        'finishing_operations',
        'unit_cost',
        'markup_multiplier',
        'unit_price',
        'total_price',
    ];

    protected $attributes = [
        'product_type' => 'commercial_print',
        'quantity' => 1000,
        'pages_count' => 1,
        'colors_front' => 4,
        'colors_back' => 0,
        'ctp_plates_count' => 4,
        'unit_cost' => '0.0000',
        'markup_multiplier' => '1.5000',
        'unit_price' => '0.0000',
        'total_price' => '0.0000',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'pages_count' => 'integer',
            'colors_front' => 'integer',
            'colors_back' => 'integer',
            'ctp_plates_count' => 'integer',
            'finishing_operations' => 'array',
            'unit_cost' => 'decimal:4',
            'markup_multiplier' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'total_price' => 'decimal:4',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }
}
