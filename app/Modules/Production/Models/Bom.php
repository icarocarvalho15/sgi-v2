<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Modules\Production\Services\BomValidationService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Bill of Materials (Ficha Técnica / Engenharia de Produto).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $name
 * @property string $product_type
 * @property int $version
 * @property string|null $parent_bom_id
 * @property bool $is_active
 * @property string|null $notes
 */
class Bom extends TenantModel
{
    use SoftDeletes;

    protected $table = 'boms';

    protected $fillable = [
        'code',
        'name',
        'product_type',
        'version',
        'parent_bom_id',
        'is_active',
        'notes',
    ];

    protected $attributes = [
        'product_type' => 'commercial_print',
        'version' => 1,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Bom $bom): void {
            if (! empty($bom->parent_bom_id) && ! empty($bom->id)) {
                BomValidationService::assertNoCycle($bom->parent_bom_id, $bom->id);
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class, 'bom_id');
    }

    public function parentBom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'parent_bom_id');
    }

    public function subBoms(): HasMany
    {
        return $this->hasMany(Bom::class, 'parent_bom_id');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'bom_id');
    }
}
