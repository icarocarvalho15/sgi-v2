<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $hourly_cost
 * @property string $capacity_per_day_hours
 * @property bool $is_active
 */
final class WorkCenter extends TenantModel
{
    protected $table = 'work_centers';

    protected $fillable = [
        'code',
        'name',
        'description',
        'hourly_cost',
        'capacity_per_day_hours',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hourly_cost' => 'decimal:4',
            'capacity_per_day_hours' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function routingSteps(): HasMany
    {
        return $this->hasMany(RoutingStep::class, 'work_center_id');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(ProductionOrderOperation::class, 'work_center_id');
    }
}
