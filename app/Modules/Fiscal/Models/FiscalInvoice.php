<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\CRM\Models\Partner;
use App\Modules\CRM\Models\SalesOrder;
use App\Modules\Fiscal\Enums\FiscalEnvironment;
use App\Modules\Fiscal\Enums\FiscalInvoiceStatus;
use App\Modules\Fiscal\Enums\FiscalInvoiceType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FiscalInvoice extends TenantModel
{
    protected $table = 'fiscal_invoices';

    protected $fillable = [
        'sales_order_id',
        'partner_id',
        'invoice_type',
        'invoice_number',
        'series',
        'environment',
        'access_key',
        'protocol_number',
        'status',
        'total_amount',
        'tax_amount',
        'xml_path',
        'pdf_path',
        'rejection_reason',
        'issued_at',
        'authorized_at',
        'cancelled_at',
        'payload',
    ];

    protected $casts = [
        'invoice_type' => FiscalInvoiceType::class,
        'status' => FiscalInvoiceStatus::class,
        'environment' => FiscalEnvironment::class,
        'total_amount' => 'string',
        'tax_amount' => 'string',
        'payload' => 'array',
        'issued_at' => 'datetime',
        'authorized_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }
}
