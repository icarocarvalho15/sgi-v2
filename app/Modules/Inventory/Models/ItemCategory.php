<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Categoria de Insumos e Produtos Gráficos.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $code
 * @property string $type
 * @property string|null $description
 */
class ItemCategory extends TenantModel
{
    use SoftDeletes;

    protected $table = 'item_categories';

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id', 'id');
    }
}
