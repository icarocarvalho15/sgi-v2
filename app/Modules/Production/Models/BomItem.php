<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\Production\Services\BomValidationService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item / Component in Bill of Materials.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $bom_id
 * @property string $item_type
 * @property string $name
 * @property string|null $child_bom_id
 * @property string $quantity
 * @property string $unit_of_measure
 * @property string $scrap_factor_percent
 * @property string|null $work_center_id
 * @property string|null $notes
 */
class BomItem extends TenantModel
{
    protected $table = 'bom_items';

    protected $fillable = [
        'bom_id',
        'item_type',
        'name',
        'child_bom_id',
        'quantity',
        'unit_of_measure',
        'scrap_factor_percent',
        'work_center_id',
        'notes',
    ];

    protected $attributes = [
        'item_type' => 'substrate',
        'quantity' => '1.0000',
        'unit_of_measure' => 'UN',
        'scrap_factor_percent' => '0.0000',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'scrap_factor_percent' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BomItem $item): void {
            if (! empty($item->child_bom_id)) {
                BomValidationService::assertNoCycle($item->bom_id, $item->child_bom_id);
            }
        });
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function childBom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'child_bom_id');
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }
}
