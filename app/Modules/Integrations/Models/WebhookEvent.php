<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Tabela Global de Idempotência e Auditoria de Webhooks.
 *
 * @property string $id
 * @property string $provider
 * @property string $external_event_id
 * @property string|null $tenant_id
 * @property string|null $event_type
 * @property string $status
 * @property array $payload
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon $created_at
 */
class WebhookEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'webhook_events';

    protected $fillable = [
        'provider',
        'external_event_id',
        'tenant_id',
        'event_type',
        'status',
        'payload',
        'error_message',
        'processed_at',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
