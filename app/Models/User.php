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
        return $this->isAdmin() || $this->role === 'sales' || $this->role === 'vendedor';
    }

    public function isProduction(): bool
    {
        return $this->isAdmin() || $this->role === 'production' || $this->role === 'operator';
    }

    public function isFinance(): bool
    {
        return $this->isAdmin() || $this->role === 'finance';
    }

    /**
     * Checks if user has any of the specified roles.
     *
     * @param string|array<string> $roles
     */
    public function hasRole(string|array $roles): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $rolesArray = is_array($roles) ? $roles : [$roles];

        // Se for admin/owner do tenant, tem acesso irrestrito
        if ($this->role === 'admin' || $this->role === 'owner') {
            return true;
        }

        return in_array($this->role, $rolesArray, true);
    }

    public function getRoleNameAttribute(): string
    {
        if ($this->is_super_admin) {
            return 'SuperAdmin SaaS';
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

        return match ($this->role) {
            'admin', 'owner' => '#6366F1', // Indigo
            'sales', 'vendedor' => '#0EA5E9', // Sky blue
            'production', 'operator' => '#F59E0B', // Amber
            'finance' => '#10B981', // Emerald
            default => '#94A3B8', // Slate
        };
    }
}
