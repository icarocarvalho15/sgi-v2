<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * WorkCenter represents machines and work stations on the shop floor:
 * CTP recorders, Offset presses, Digital plotters, Guillotines, Laser engravers.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $code
 * @property string $type
 * @property string $hourly_rate
 * @property string $capacity_per_hour
 * @property bool $is_active
 * @property string|null $description
 */
class WorkCenter extends TenantModel
{
    use SoftDeletes;

    protected $table = 'work_centers';

    protected $fillable = [
        'name',
        'code',
        'type',
        'hourly_rate',
        'capacity_per_hour',
        'is_active',
        'description',
    ];

    protected $attributes = [
        'hourly_rate' => '0.0000',
        'capacity_per_hour' => '0.0000',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:4',
            'capacity_per_hour' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function bomItems(): HasMany
    {
        return $this->hasMany(BomItem::class, 'work_center_id');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(ProductionTimeLog::class, 'work_center_id');
    }
}
