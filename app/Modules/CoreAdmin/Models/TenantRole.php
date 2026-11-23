<?php

declare(strict_types=1);

namespace App\Modules\CoreAdmin\Models;

use App\Core\Tenancy\Models\TenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * TenantRole represents customizable user roles and positions per Tenant.
 * Enables Print Shops & Custom Gift businesses to define their own job titles
 * (e.g. "Orçamentista Gráfico", "Designer / CTP", "Impressor Offset")
 * and assign granular module permissions.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $color
 * @property array $permissions
 * @property bool $is_system
 */
class TenantRole extends TenantModel
{
    use SoftDeletes;

    protected $table = 'tenant_roles';

    protected $attributes = [
        'color' => '#6366F1',
        'permissions' => '[]',
        'is_system' => false,
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'permissions',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (TenantRole $role): void {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->name);
            }
        });

        static::updating(function (TenantRole $role): void {
            if ($role->isDirty('name') && ! $role->is_system && ! $role->isDirty('slug')) {
                $role->slug = Str::slug($role->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /**
     * Checks if this role has permission to access a specific module.
     */
    public function hasPermission(string $module): bool
    {
        if ($this->is_system && ($this->slug === 'admin' || $this->slug === 'owner')) {
            return true;
        }

        $perms = $this->permissions ?? [];

        if (in_array('*', $perms, true)) {
            return true;
        }

        return in_array($module, $perms, true);
    }

    /**
     * Scope to order roles (system/admin first, then by name).
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('is_system', 'desc')->orderBy('name');
    }
}
