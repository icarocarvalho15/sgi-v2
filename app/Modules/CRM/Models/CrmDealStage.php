<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $pipeline_id
 * @property string $name
 * @property int $sequence
 * @property string $probability_percentage
 * @property string $color
 * @property bool $is_won
 * @property bool $is_lost
 */
final class CrmDealStage extends TenantModel
{
    protected $table = 'crm_deal_stages';

    protected $fillable = [
        'pipeline_id',
        'name',
        'sequence',
        'probability_percentage',
        'color',
        'is_won',
        'is_lost',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'probability_percentage' => 'decimal:2',
            'is_won' => 'boolean',
            'is_lost' => 'boolean',
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
     * @return HasMany<CrmDeal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'stage_id');
    }
}
