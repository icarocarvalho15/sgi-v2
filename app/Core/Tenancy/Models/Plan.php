<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Plan represents the SaaS subscription tiers for printing and gift businesses.
 * e.g., "Plano Offset & Digital", "Plano Birô CTP", "Plano Indústria de Brindes Enterprise".
 */
class Plan extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'plans';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_interval',
        'max_users',
        'max_monthly_orders',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'max_users' => 'integer',
            'max_monthly_orders' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'plan_id');
    }
}
