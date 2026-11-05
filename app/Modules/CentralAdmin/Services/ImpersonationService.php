<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\Services;

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\CentralAdmin\Models\ImpersonationLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

final class ImpersonationService
{
    private const SESSION_KEY = 'impersonation_log_id';
    private const ORIGINAL_USER_KEY = 'impersonator_original_user_id';

    /**
     * Starts an audited impersonation session for support.
     * Only users with is_super_admin = true can initiate impersonation.
     */
    public static function start(User $superAdmin, Tenant $targetTenant, string $reason, ?string $ip = null, ?string $userAgent = null): ImpersonationLog
    {
        if (! $superAdmin->isSuperAdmin()) {
            throw new AuthorizationException('Apenas SuperAdmins auditados têm permissão para iniciar sessões de impersonation.');
        }

        if (trim($reason) === '') {
            throw new \InvalidArgumentException('É obrigatório justificar o motivo/chamado de suporte para impersonation.');
        }

        $log = ImpersonationLog::create([
            'impersonator_id' => $superAdmin->id,
            'tenant_id' => $targetTenant->id,
            'reason' => $reason,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'started_at' => now(),
        ]);

        Session::put(self::SESSION_KEY, $log->id);
        Session::put(self::ORIGINAL_USER_KEY, $superAdmin->id);

        // Sets tenant context in application and PostgreSQL session
        TenantContext::set($targetTenant);

        return $log;
    }

    /**
     * Ends the current impersonation session and records the timestamp.
     */
    public static function stop(): ?ImpersonationLog
    {
        $logId = Session::get(self::SESSION_KEY);

        if ($logId !== null) {
            $log = ImpersonationLog::find($logId);
            if ($log !== null) {
                $log->update(['ended_at' => now()]);
            }

            Session::forget(self::SESSION_KEY);
            Session::forget(self::ORIGINAL_USER_KEY);

            TenantContext::clear();

            return $log;
        }

        TenantContext::clear();
        return null;
    }

    /**
     * Checks if the current request is under an active impersonation session.
     */
    public static function isImpersonating(): bool
    {
        return Session::has(self::SESSION_KEY);
    }
}
