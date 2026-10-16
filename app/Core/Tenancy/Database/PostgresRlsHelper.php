<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Database;

use Illuminate\Support\Facades\DB;

final class PostgresRlsHelper
{
    public const POLICY_NAME = 'tenant_isolation_policy';

    /**
     * Aplica e força Row-Level Security com política Restrictive de Tenant na tabela fornecida.
     */
    public static function enableTenantRls(string $tableName): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE {$tableName} ENABLE ROW LEVEL SECURITY;");
        DB::statement("ALTER TABLE {$tableName} FORCE ROW LEVEL SECURITY;");

        DB::statement(self::getCreatePolicySql($tableName));
    }

    /**
     * Remove política de RLS e desativa Row-Level Security da tabela.
     */
    public static function disableTenantRls(string $tableName): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS '.self::POLICY_NAME." ON {$tableName};");
        DB::statement("ALTER TABLE {$tableName} NO FORCE ROW LEVEL SECURITY;");
        DB::statement("ALTER TABLE {$tableName} DISABLE ROW LEVEL SECURITY;");
    }

    /**
     * Retorna a string SQL exata da política de isolamento PostgreSQL RLS para a tabela.
     */
    public static function getCreatePolicySql(string $tableName): string
    {
        $policy = self::POLICY_NAME;

        return <<<SQL
        CREATE POLICY {$policy} ON {$tableName}
            AS RESTRICTIVE
            FOR ALL
            USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::uuid)
            WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::uuid);
        SQL;
    }
}
