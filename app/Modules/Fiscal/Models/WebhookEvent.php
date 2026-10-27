<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Fiscal\Enums\PaymentGateway;
use App\Modules\Fiscal\Enums\WebhookEventStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WebhookEvent extends TenantModel
{
    protected $table = 'webhook_events';

    protected $fillable = [
        'gateway',
        'external_event_id',
        'event_type',
        'financial_entry_id',
        'payload',
        'status',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'gateway' => PaymentGateway::class,
        'payload' => 'array',
        'status' => WebhookEventStatus::class,
        'processed_at' => 'datetime',
    ];

    public function financialEntry(): BelongsTo
    {
        return $this->belongsTo(FinancialEntry::class, 'financial_entry_id');
    }
}
