<?php

declare(strict_types=1);

namespace App\Modules\CoreAdmin\Models;

use App\Core\Tenancy\Models\TenantModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * TenantStatus represents customizable operational statuses per Tenant.
 * Enables each Print Shop or Gift Manufacturer to configure their own stages
 * for Quotes, Production Orders, and Sales Pipelines.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $module
 * @property string $name
 * @property string $slug
 * @property string $color
 * @property int $position
 * @property bool $is_default
 * @property bool $is_final
 * @property array|null $metadata
 */
class TenantStatus extends TenantModel
{
    protected $table = 'tenant_statuses';

    protected $attributes = [
        'color' => '#64748B',
        'position' => 0,
        'is_default' => false,
        'is_final' => false,
    ];

    protected $fillable = [
        'module',
        'name',
        'slug',
        'color',
        'position',
        'is_default',
        'is_final',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_default' => 'boolean',
            'is_final' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TenantStatus $status): void {
            if (empty($status->slug) && ! empty($status->name)) {
                $status->slug = Str::slug($status->name);
            }
        });
    }

    /**
     * Scope to filter statuses by operational module.
     */
    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Scope to order statuses by defined position.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }

    /**
     * Marks this status as the default initial status for its module,
     * un-marking any existing default status in the same tenant and module.
     */
    public function markAsDefault(): void
    {
        // Unset any current default for the same module within the tenant
        static::query()
            ->where('module', $this->module)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
