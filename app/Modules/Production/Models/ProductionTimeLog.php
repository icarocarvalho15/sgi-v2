<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shop-floor operation time and scrap log.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $production_order_id
 * @property string $work_center_id
 * @property string|null $user_id
 * @property string $operation_name
 * @property string $started_at
 * @property string|null $ended_at
 * @property int $good_quantity
 * @property int $scrap_quantity
 * @property string|null $notes
 */
class ProductionTimeLog extends TenantModel
{
    protected $table = 'production_time_logs';

    protected $fillable = [
        'production_order_id',
        'work_center_id',
        'user_id',
        'operation_name',
        'started_at',
        'ended_at',
        'good_quantity',
        'scrap_quantity',
        'notes',
    ];

    protected $attributes = [
        'good_quantity' => 0,
        'scrap_quantity' => 0,
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'good_quantity' => 'integer',
            'scrap_quantity' => 'integer',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
