<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use App\Core\Tenancy\Database\PostgresRlsHelper;
use App\Core\Tenancy\Models\Tenant;

final class TenantContext
{
    private static ?string $tenantId = null;
    private static ?Tenant $tenant = null;

    /**
     * Sets the active tenant context for the application and synchronizes
     * with the PostgreSQL Row-Level Security (RLS) session variable.
     */
    public static function set(Tenant|string $tenant): void
    {
        if ($tenant instanceof Tenant) {
            self::$tenant = $tenant;
            self::$tenantId = (string) $tenant->id;
        } else {
            self::$tenantId = $tenant;
            self::$tenant = null;
        }

        // Synchronize with PostgreSQL session variable
        PostgresRlsHelper::setSessionTenant(self::$tenantId);
    }

    /**
     * Retrieves the current active Tenant entity.
     */
    public static function get(): ?Tenant
    {
        if (self::$tenant !== null) {
            return self::$tenant;
        }

        if (self::$tenantId !== null) {
            self::$tenant = Tenant::find(self::$tenantId);
            return self::$tenant;
        }

        return null;
    }

    /**
     * Retrieves the active tenant UUID string.
     */
    public static function id(): ?string
    {
        return self::$tenantId;
    }

    /**
     * Verifies if a tenant context is currently active.
     */
    public static function check(): bool
    {
        return self::$tenantId !== null && self::$tenantId !== '';
    }

    /**
     * Clears the tenant context in memory and resets the PostgreSQL session setting.
     */
    public static function clear(): void
    {
        self::$tenantId = null;
        self::$tenant = null;

        PostgresRlsHelper::resetSessionTenant();
    }
}
