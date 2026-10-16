<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $price
 * @property string $billing_interval
 * @property int $max_users
 * @property int $max_storage_mb
 * @property array<string, mixed>|null $features
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 */
class Plan extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_interval',
        'max_users',
        'max_storage_mb',
        'features',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'max_users' => 'integer',
            'max_storage_mb' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'plan_id');
    }

    public function hasFeature(string $featureKey): bool
    {
        if ($this->features === null) {
            return false;
        }

        return (bool) ($this->features[$featureKey] ?? false);
    }

    public function getFeatureLimit(string $limitKey, int $default = 0): int
    {
        if ($this->features === null) {
            return $default;
        }

        return (int) ($this->features[$limitKey] ?? $default);
    }
}
