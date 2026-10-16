<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\Actions;

use App\Core\Tenancy\Models\ImpersonationLog;

final class StopImpersonationAction
{
    /**
     * Encerra a sessão de impersonation ativa e preenche o timestamp ended_at no log.
     */
    public function execute(): ?ImpersonationLog
    {
        $logId = session()->get('impersonation_log_id');
        $log = null;

        if (is_string($logId) && $logId !== '') {
            $log = ImpersonationLog::query()->find($logId);
            if ($log instanceof ImpersonationLog) {
                $log->update(['ended_at' => now()]);
            }
        }

        session()->forget([
            'impersonation_log_id',
            'is_impersonating',
            'impersonated_tenant_name',
            'active_tenant_id',
        ]);

        return $log;
    }
}
