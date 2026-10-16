<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Models\User;
use App\Modules\Production\Enums\OperationStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $production_order_id
 * @property string|null $routing_step_id
 * @property string $work_center_id
 * @property int $sequence
 * @property OperationStatus $status
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $paused_at
 * @property CarbonInterface|null $completed_at
 * @property int $executed_time_seconds
 * @property string|null $operator_user_id
 * @property string|null $notes
 */
final class ProductionOrderOperation extends TenantModel
{
    protected $table = 'production_order_operations';

    protected $fillable = [
        'production_order_id',
        'routing_step_id',
        'work_center_id',
        'sequence',
        'status',
        'started_at',
        'paused_at',
        'completed_at',
        'executed_time_seconds',
        'operator_user_id',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'status' => OperationStatus::class,
            'started_at' => 'datetime',
            'paused_at' => 'datetime',
            'completed_at' => 'datetime',
            'executed_time_seconds' => 'integer',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function routingStep(): BelongsTo
    {
        return $this->belongsTo(RoutingStep::class, 'routing_step_id');
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }
}
