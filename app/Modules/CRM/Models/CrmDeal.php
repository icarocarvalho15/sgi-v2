<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Models\User;
use App\Modules\CRM\Enums\DealPriority;
use App\Modules\CRM\Enums\DealStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $pipeline_id
 * @property string $stage_id
 * @property string $partner_id
 * @property string $title
 * @property string $estimated_value
 * @property CarbonInterface|null $expected_close_date
 * @property DealStatus $status
 * @property DealPriority $priority
 * @property string|null $assigned_user_id
 * @property string|null $lost_reason
 * @property string|null $notes
 */
final class CrmDeal extends TenantModel
{
    protected $table = 'crm_deals';

    protected $fillable = [
        'pipeline_id',
        'stage_id',
        'partner_id',
        'title',
        'estimated_value',
        'expected_close_date',
        'status',
        'priority',
        'assigned_user_id',
        'lost_reason',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'expected_close_date' => 'date',
            'status' => DealStatus::class,
            'priority' => DealPriority::class,
        ];
    }

    /**
     * @return BelongsTo<CrmPipeline, $this>
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'pipeline_id');
    }

    /**
     * @return BelongsTo<CrmDealStage, $this>
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(CrmDealStage::class, 'stage_id');
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * @return HasMany<Quote, $this>
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'deal_id');
    }

    /**
     * @return HasMany<SalesOrder, $this>
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'deal_id');
    }
}
