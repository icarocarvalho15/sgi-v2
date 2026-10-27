<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property bool $is_default
 * @property bool $is_active
 */
final class CrmPipeline extends TenantModel
{
    protected $table = 'crm_pipelines';

    protected $fillable = [
        'name',
        'is_default',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CrmDealStage, $this>
     */
    public function stages(): HasMany
    {
        return $this->hasMany(CrmDealStage::class, 'pipeline_id')->orderBy('sequence');
    }

    /**
     * @return HasMany<CrmDeal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'pipeline_id');
    }
}
