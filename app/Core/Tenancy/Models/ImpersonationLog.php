<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $impersonator_id
 * @property string $tenant_id
 * @property string|null $impersonated_user_id
 * @property string $reason
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $ended_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class ImpersonationLog extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'impersonation_logs';

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

    /**
     * @return array<string, string>
     */
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
