<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $code
 * @property string $symbol
 * @property string $type
 * @property bool $is_active
 */
final class UnitOfMeasure extends TenantModel
{
    protected $table = 'units_of_measure';

    protected $fillable = [
        'name',
        'code',
        'symbol',
        'type',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'unit_of_measure_id');
    }
}
