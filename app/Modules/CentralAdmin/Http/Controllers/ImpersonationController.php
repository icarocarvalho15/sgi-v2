<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\Http\Controllers;

use App\Models\User;
use App\Modules\CentralAdmin\Actions\StartImpersonationAction;
use App\Modules\CentralAdmin\Actions\StopImpersonationAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ImpersonationController
{
    public function start(
        Request $request,
        string $tenantId,
        StartImpersonationAction $action
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        /** @var User $superAdmin */
        $superAdmin = $request->user();

        $action->execute($superAdmin, $tenantId, (string) $validated['reason'], $request);

        return redirect()->route('tenant.dashboard')->with('success', 'Modo Impersonation iniciado. Você está atuando no contexto desta empresa.');
    }

    public function stop(StopImpersonationAction $action): RedirectResponse
    {
        $action->execute();

        return redirect()->route('admin.dashboard')->with('info', 'Sessão de suporte via impersonation encerrada com sucesso.');
    }
}
