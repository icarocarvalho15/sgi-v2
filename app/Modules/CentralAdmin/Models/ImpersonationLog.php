<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\Models;

use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit log entity for SuperAdmin technical support impersonation sessions.
 * This is a Landlord model (not tenant-scoped).
 */
class ImpersonationLog extends Model
{
    use HasUuids;

    protected $table = 'impersonation_logs';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'impersonator_id',
        'tenant_id',
        'impersonated_user_id',
        'reason',
        'ip_address',
        'user_agent',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonator_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function impersonatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonated_user_id');
    }
}
