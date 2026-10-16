<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\Actions;

use App\Core\Tenancy\Models\Tenant;
use InvalidArgumentException;

final class UpdateTenantStatusAction
{
    private const ALLOWED_STATUSES = ['active', 'trial', 'suspended', 'canceled'];

    public function execute(string $tenantId, string $status): Tenant
    {
        if (! in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException("Status '{$status}' não é válido para um Tenant.");
        }

        /** @var Tenant $tenant */
        $tenant = Tenant::query()->findOrFail($tenantId);
        $tenant->update(['status' => $status]);

        // Sincroniza status na assinatura ativa mais recente
        $subscription = $tenant->subscriptions()->latest('created_at')->first();
        if ($subscription !== null) {
            $subscriptionStatus = $status === 'active' ? 'active' : ($status === 'suspended' ? 'past_due' : 'canceled');
            $subscription->update(['status' => $subscriptionStatus]);
        }

        return $tenant;
    }
}
