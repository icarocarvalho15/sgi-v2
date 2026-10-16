<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\Actions;

use App\Core\Tenancy\Models\ImpersonationLog;
use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class StartImpersonationAction
{
    /**
     * Inicia a sessão de impersonation registrando log compulsório de auditoria.
     */
    public function execute(User $superAdmin, string $tenantId, string $reason, Request $request): ImpersonationLog
    {
        if (! $superAdmin->is_super_admin) {
            throw new InvalidArgumentException('Apenas super administradores podem iniciar sessões de impersonation.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('O motivo do acesso de suporte via impersonation é estritamente obrigatório.');
        }

        /** @var Tenant $tenant */
        $tenant = Tenant::query()->findOrFail($tenantId);

        /** @var ImpersonationLog $log */
        $log = ImpersonationLog::query()->create([
            'impersonator_id' => $superAdmin->id,
            'tenant_id' => $tenant->id,
            'reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'started_at' => now(),
        ]);

        // Grava na sessão HTTP os marcadores de impersonation
        session()->put('active_tenant_id', $tenant->id);
        session()->put('impersonation_log_id', $log->id);
        session()->put('is_impersonating', true);
        session()->put('impersonated_tenant_name', $tenant->name);

        return $log;
    }
}
