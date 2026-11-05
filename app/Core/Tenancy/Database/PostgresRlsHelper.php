<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Database;

use Illuminate\Support\Facades\DB;

final class PostgresRlsHelper
{
    /**
     * Applies strict PostgreSQL Row-Level Security (RLS) to a tenant-scoped table.
     * Enforces Double-Lock isolation:
     * 1. Enables RLS on table.
     * 2. Forces RLS on table (FORCE ROW LEVEL SECURITY).
     * 3. Creates restrictive tenant isolation policy checking session variable 'app.current_tenant_id'.
     */
    public static function applyRlsToTable(string $tableName): void
    {
        DB::statement("ALTER TABLE {$tableName} ENABLE ROW LEVEL SECURITY;");
        DB::statement("ALTER TABLE {$tableName} FORCE ROW LEVEL SECURITY;");

        DB::statement("DROP POLICY IF EXISTS tenant_isolation_policy ON {$tableName};");

        DB::statement("
            CREATE POLICY tenant_isolation_policy ON {$tableName}
            FOR ALL
            USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::uuid)
            WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::uuid);
        ");
    }

    /**
     * Drops PostgreSQL Row-Level Security policy and disables RLS on the table (used in down() migrations).
     */
    public static function dropRlsFromTable(string $tableName): void
    {
        DB::statement("DROP POLICY IF EXISTS tenant_isolation_policy ON {$tableName};");
        DB::statement("ALTER TABLE {$tableName} DISABLE ROW LEVEL SECURITY;");
    }

    /**
     * Alias conforming to the Security Rules naming convention.
     */
    public static function enableTenantRls(string $tableName): void
    {
        self::applyRlsToTable($tableName);
    }

    /**
     * Sets the active tenant in the current PostgreSQL database session.
     */
    public static function setSessionTenant(string $tenantId): void
    {
        DB::statement("SELECT set_config('app.current_tenant_id', ?, false)", [$tenantId]);
    }

    /**
     * Resets the active tenant in the current PostgreSQL database session.
     */
    public static function resetSessionTenant(): void
    {
        DB::statement("SELECT set_config('app.current_tenant_id', '', false)");
        DB::statement("RESET app.current_tenant_id");
    }
}
