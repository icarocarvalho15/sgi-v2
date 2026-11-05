<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\CoreAdmin\Models\TenantStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CrmDeal represents an opportunity in the Sales Kanban.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $partner_id
 * @property string $status_id
 * @property string $title
 * @property string $estimated_value
 * @property int $probability_percent
 * @property string|null $expected_close_date
 * @property string|null $notes
 */
class CrmDeal extends TenantModel
{
    use SoftDeletes;

    protected $table = 'crm_deals';

    protected $fillable = [
        'partner_id',
        'status_id',
        'title',
        'estimated_value',
        'probability_percent',
        'expected_close_date',
        'assigned_user_id',
        'notes',
    ];

    protected $attributes = [
        'estimated_value' => '0.0000',
        'probability_percent' => 50,
    ];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:4',
            'probability_percent' => 'integer',
            'expected_close_date' => 'date',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TenantStatus::class, 'status_id');
    }
}
