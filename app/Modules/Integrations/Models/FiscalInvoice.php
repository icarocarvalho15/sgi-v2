<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\CRM\Models\Partner;
use App\Modules\SalesOrders\Models\SalesOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Nota Fiscal Eletrônica (NFe / NFSe).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $type
 * @property string|null $sales_order_id
 * @property string|null $partner_id
 * @property string $status
 * @property int|null $invoice_number
 * @property int $series
 * @property string|null $access_key
 * @property string|null $protocol_number
 * @property string|null $xml_path
 * @property string|null $pdf_danfe_path
 * @property string|null $rejection_reason
 * @property string $total_amount
 * @property array|null $items_data
 * @property \Illuminate\Support\Carbon|null $issued_at
 */
class FiscalInvoice extends TenantModel
{
    use SoftDeletes;

    protected $table = 'fiscal_invoices';

    protected $fillable = [
        'type',
        'sales_order_id',
        'partner_id',
        'status',
        'invoice_number',
        'series',
        'access_key',
        'protocol_number',
        'xml_path',
        'pdf_danfe_path',
        'rejection_reason',
        'total_amount',
        'items_data',
        'issued_at',
    ];

    protected $casts = [
        'invoice_number' => 'integer',
        'series' => 'integer',
        'total_amount' => 'string',
        'items_data' => 'array',
        'issued_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }
}
