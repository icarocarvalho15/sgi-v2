<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tenant represents the subscriber company (e.g., Gráfica, Indústria de Brindes, Birô de CTP/Fotolito).
 * This is a Landlord entity and does NOT extend TenantModel because it is the tenant root.
 */
class Tenant extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'tenants';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'plan_id',
        'name',
        'trade_name',
        'document_number',
        'subdomain',
        'domain',
        'status',
        'trial_ends_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(\App\Modules\CoreAdmin\Models\TenantStatus::class, 'tenant_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
