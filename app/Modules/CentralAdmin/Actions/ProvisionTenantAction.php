<?php

declare(strict_types=1);

namespace App\Modules\CentralAdmin\Actions;

use App\Core\Tenancy\Models\Plan;
use App\Core\Tenancy\Models\Subscription;
use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use App\Modules\CentralAdmin\DTOs\ProvisionTenantDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Throwable;

final class ProvisionTenantAction
{
    /**
     * Executa o provisionamento atômico de um Tenant, Dono e Assinatura.
     *
     * @throws Throwable
     */
    public function execute(ProvisionTenantDTO $dto): Tenant
    {
        return DB::transaction(function () use ($dto): Tenant {
            $plan = Plan::query()->findOrFail($dto->planId);

            // 1. Criar o Tenant
            /** @var Tenant $tenant */
            $tenant = Tenant::query()->create([
                'name' => $dto->name,
                'trade_name' => $dto->tradeName,
                'document_number' => $dto->documentNumber,
                'subdomain' => $dto->subdomain,
                'plan_id' => $plan->id,
                'status' => 'active',
            ]);

            // 2. Criar ou Vincular o Usuário Proprietário
            /** @var User $owner */
            $owner = User::query()->firstOrCreate(
                ['email' => $dto->ownerEmail],
                [
                    'name' => $dto->ownerName,
                    'password' => Hash::make($dto->ownerPassword),
                    'is_super_admin' => false,
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            // 3. Associar no pivot tenant_user
            $tenant->users()->attach($owner->id, [
                'role' => 'admin',
                'is_owner' => true,
            ]);

            // 4. Criar Assinatura Inicial
            Subscription::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'gateway' => 'internal',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);

            // 5. Configurar Papéis Iniciais do Tenant
            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($tenant->id);

                $adminRole = Role::query()->firstOrCreate([
                    'name' => 'Admin da Indústria',
                    'guard_name' => 'web',
                    'tenant_id' => $tenant->id,
                ]);

                $owner->assignRole($adminRole);

                // Outros papéis padrão
                $defaultRoles = ['Engenharia', 'PCP', 'Operador de Fábrica', 'Financeiro', 'Vendas'];
                foreach ($defaultRoles as $rName) {
                    Role::query()->firstOrCreate([
                        'name' => $rName,
                        'guard_name' => 'web',
                        'tenant_id' => $tenant->id,
                    ]);
                }

                setPermissionsTeamId(null);
            }

            return $tenant;
        });
    }
}
