<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Tenancy\Models\Tenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $attributes = [
        'role' => 'admin',
        'is_active' => true,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'role',
        'role_id',
        'is_active',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'role_name',
        'role_color',
        'allowed_modules',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    public function tenantRole(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\CoreAdmin\Models\TenantRole::class, 'role_id');
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isAdmin(): bool
    {
        return $this->is_super_admin || $this->role === 'admin' || $this->role === 'owner';
    }

    public function isSales(): bool
    {
        return $this->isAdmin() || $this->hasPermissionToModule('quotes');
    }

    public function isProduction(): bool
    {
        return $this->isAdmin() || $this->hasPermissionToModule('production');
    }

    public function isFinance(): bool
    {
        return $this->isAdmin() || $this->hasPermissionToModule('finance');
    }

    /**
     * Resolves the list of modules this user is authorized to access.
     *
     * @return array<int, string>
     */
    public function getAllowedModulesAttribute(): array
    {
        if ($this->isAdmin()) {
            return ['*'];
        }

        // 1. Check direct role_id relation
        if ($this->role_id) {
            $roleObj = $this->relationLoaded('tenantRole') ? $this->tenantRole : \App\Modules\CoreAdmin\Models\TenantRole::find($this->role_id);
            if ($roleObj) {
                return $roleObj->permissions ?? [];
            }
        }

        // 2. Check role slug in tenant_roles table
        if (! empty($this->tenant_id) && ! empty($this->role)) {
            $roleObj = \App\Modules\CoreAdmin\Models\TenantRole::where('tenant_id', $this->tenant_id)
                ->where('slug', $this->role)
                ->first();

            if ($roleObj) {
                return $roleObj->permissions ?? [];
            }
        }

        // 3. Fallback standard mappings
        return match ($this->role) {
            'sales', 'vendedor' => ['crm', 'quotes', 'inventory'],
            'production', 'operator' => ['boms', 'production', 'inventory', 'kardex'],
            'finance' => ['crm', 'finance', 'reports', 'settings_fiscal'],
            default => [],
        };
    }

    /**
     * Checks if user has permission to access a specific module.
     */
    public function hasPermissionToModule(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $allowed = $this->allowed_modules;

        if (in_array('*', $allowed, true)) {
            return true;
        }

        return in_array($module, $allowed, true);
    }

    /**
     * Checks if user has any of the specified roles or module permissions.
     *
     * @param string|array<string> $roles
     */
    public function hasRole(string|array $roles): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $rolesArray = is_array($roles) ? $roles : [$roles];

        // 1. Match direct role slug
        if (in_array($this->role, $rolesArray, true)) {
            return true;
        }

        // 2. Match module permissions
        foreach ($rolesArray as $req) {
            if ($this->hasPermissionToModule($req)) {
                return true;
            }
        }

        return false;
    }

    public function getRoleNameAttribute(): string
    {
        if ($this->is_super_admin) {
            return 'SuperAdmin SaaS';
        }

        // 1. Direct role relation
        if ($this->role_id) {
            $roleObj = $this->relationLoaded('tenantRole') ? $this->tenantRole : \App\Modules\CoreAdmin\Models\TenantRole::find($this->role_id);
            if ($roleObj) {
                return $roleObj->name;
            }
        }

        // 2. Slug lookup in tenant_roles
        if (! empty($this->tenant_id) && ! empty($this->role)) {
            $roleObj = \App\Modules\CoreAdmin\Models\TenantRole::where('tenant_id', $this->tenant_id)
                ->where('slug', $this->role)
                ->first();

            if ($roleObj) {
                return $roleObj->name;
            }
        }

        return match ($this->role) {
            'admin', 'owner' => 'Dono / Administrador',
            'sales', 'vendedor' => 'Vendedor / Comercial',
            'production', 'operator' => 'Chão de Fábrica / PCP',
            'finance' => 'Financeiro / Fiscal',
            default => 'Membro',
        };
    }

    public function getRoleColorAttribute(): string
    {
        if ($this->is_super_admin) {
            return '#A855F7'; // Roxo
        }

        // 1. Direct role relation
        if ($this->role_id) {
            $roleObj = $this->relationLoaded('tenantRole') ? $this->tenantRole : \App\Modules\CoreAdmin\Models\TenantRole::find($this->role_id);
            if ($roleObj) {
                return $roleObj->color;
            }
        }

        // 2. Slug lookup in tenant_roles
        if (! empty($this->tenant_id) && ! empty($this->role)) {
            $roleObj = \App\Modules\CoreAdmin\Models\TenantRole::where('tenant_id', $this->tenant_id)
                ->where('slug', $this->role)
                ->first();

            if ($roleObj) {
                return $roleObj->color;
            }
        }

        return match ($this->role) {
            'admin', 'owner' => '#6366F1', // Indigo
            'sales', 'vendedor' => '#0EA5E9', // Sky blue
            'production', 'operator' => '#F59E0B', // Amber
            'finance' => '#10B981', // Emerald
            default => '#94A3B8', // Slate
        };
    }
}
