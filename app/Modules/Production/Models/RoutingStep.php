<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $bom_id
 * @property string $work_center_id
 * @property int $sequence
 * @property string $name
 * @property string|null $description
 * @property string $setup_time_minutes
 * @property string $run_time_minutes_per_unit
 */
final class RoutingStep extends TenantModel
{
    protected $table = 'routing_steps';

    protected $fillable = [
        'bom_id',
        'work_center_id',
        'sequence',
        'name',
        'description',
        'setup_time_minutes',
        'run_time_minutes_per_unit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'setup_time_minutes' => 'decimal:2',
            'run_time_minutes_per_unit' => 'decimal:2',
        ];
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }
}
