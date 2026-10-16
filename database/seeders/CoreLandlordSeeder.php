<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Tenancy\Models\Plan;
use App\Core\Tenancy\Models\Subscription;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use App\Modules\Inventory\Models\UnitOfMeasure;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class CoreLandlordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reseta cache de permissões do Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Criar os 3 Planos SaaS Padrão
        $starterPlan = Plan::query()->firstOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter Industrial',
                'description' => 'Ideal para pequenas oficinas e indústrias iniciantes.',
                'price' => 199.00,
                'billing_interval' => 'monthly',
                'max_users' => 3,
                'max_storage_mb' => 1024,
                'is_active' => true,
                'features' => [
                    'crm' => true,
                    'inventory' => true,
                    'engineering' => false,
                    'production' => false,
                    'finance' => false,
                    'nfe_enabled' => false,
                    'pwa_shopfloor' => false,
                ],
            ]
        );

        $proPlan = Plan::query()->firstOrCreate(
            ['slug' => 'industrial-pro'],
            [
                'name' => 'Industrial Pro',
                'description' => 'Controle completo de engenharia, PCP, Kardex e apontamento de chão de fábrica.',
                'price' => 499.00,
                'billing_interval' => 'monthly',
                'max_users' => 10,
                'max_storage_mb' => 5120,
                'is_active' => true,
                'features' => [
                    'crm' => true,
                    'inventory' => true,
                    'engineering' => true,
                    'production' => true,
                    'finance' => true,
                    'nfe_enabled' => true,
                    'pwa_shopfloor' => true,
                ],
            ]
        );

        $enterprisePlan = Plan::query()->firstOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise Custom',
                'description' => 'Operações industriais avançadas com alta demanda, múltiplos turnos e suporte dedicado.',
                'price' => 999.00,
                'billing_interval' => 'monthly',
                'max_users' => 50,
                'max_storage_mb' => 20480,
                'is_active' => true,
                'features' => [
                    'crm' => true,
                    'inventory' => true,
                    'engineering' => true,
                    'production' => true,
                    'finance' => true,
                    'nfe_enabled' => true,
                    'pwa_shopfloor' => true,
                    'custom_reports' => true,
                    'priority_queue' => true,
                ],
            ]
        );

        // 2. Criar Super Admin (Dono do SaaS / Landlord)
        User::query()->firstOrCreate(
            ['email' => 'admin@sgi.com.br'],
            [
                'name' => 'Super Admin (Landlord)',
                'password' => Hash::make('password'),
                'is_super_admin' => true,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // 3. Permissões padrão do sistema
        $permissionNames = [
            'tenants.manage',
            'inventory.view',
            'inventory.manage',
            'engineering.view',
            'engineering.manage',
            'production.view',
            'production.execute',
            'crm.view',
            'crm.manage',
            'finance.view',
            'finance.manage',
        ];

        foreach ($permissionNames as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // 4. Criação dos Tenants de Demonstração
        $demoTenants = [
            [
                'name' => 'Metalúrgica Alfa Ltda',
                'trade_name' => 'Alfa Usinagem & Caldeiraria',
                'document_number' => '12.345.678/0001-90',
                'subdomain' => 'alfa',
                'plan_id' => $proPlan->id,
                'owner_name' => 'Carlos Engenheiro',
                'owner_email' => 'alfa.admin@metalurgicaalfa.com.br',
            ],
            [
                'name' => 'Indústria Beta S/A',
                'trade_name' => 'Beta Componentes Mecânicos',
                'document_number' => '98.765.432/0001-10',
                'subdomain' => 'beta',
                'plan_id' => $enterprisePlan->id,
                'owner_name' => 'Mariana Diretora',
                'owner_email' => 'beta.admin@industriabeta.com.br',
            ],
        ];

        $rolesToCreate = [
            'Admin da Indústria',
            'Engenharia',
            'PCP',
            'Operador de Fábrica',
            'Financeiro',
            'Vendas',
        ];

        foreach ($demoTenants as $data) {
            /** @var Tenant $tenant */
            $tenant = Tenant::query()->firstOrCreate(
                ['subdomain' => $data['subdomain']],
                [
                    'name' => $data['name'],
                    'trade_name' => $data['trade_name'],
                    'document_number' => $data['document_number'],
                    'plan_id' => $data['plan_id'],
                    'status' => 'active',
                ]
            );

            // Assinatura ativa vinculada ao plano
            Subscription::query()->firstOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'plan_id' => $tenant->plan_id,
                    'status' => 'active',
                    'gateway' => 'internal',
                    'starts_at' => now(),
                    'ends_at' => now()->addYear(),
                ]
            );

            // Usuário Dono/Admin do Tenant
            /** @var User $owner */
            $owner = User::query()->firstOrCreate(
                ['email' => $data['owner_email']],
                [
                    'name' => $data['owner_name'],
                    'password' => Hash::make('password'),
                    'is_super_admin' => false,
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            // Vincula o usuário ao Tenant no pivot
            if (! $tenant->users()->where('users.id', $owner->id)->exists()) {
                $tenant->users()->attach($owner->id, [
                    'role' => 'admin',
                    'is_owner' => true,
                ]);
            }

            // Define o team_id do Spatie Permission para este Tenant
            setPermissionsTeamId($tenant->id);

            // Cria papéis escopados para o Tenant
            foreach ($rolesToCreate as $roleName) {
                /** @var Role $role */
                $role = Role::query()->firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'tenant_id' => $tenant->id,
                ]);

                if ($roleName === 'Admin da Indústria') {
                    $role->syncPermissions($permissionNames);
                    // Atribui o papel de Admin ao dono do Tenant
                    $owner->assignRole($role);
                }
            }

            // Define o contexto do Tenant para alimentar dados iniciais de Suprimentos
            app(TenantContext::class)->set($tenant);

            $defaultUnits = [
                ['name' => 'Unidade', 'code' => 'UN', 'symbol' => 'un', 'type' => 'unit'],
                ['name' => 'Quilograma', 'code' => 'KG', 'symbol' => 'kg', 'type' => 'weight'],
                ['name' => 'Metro', 'code' => 'M', 'symbol' => 'm', 'type' => 'length'],
                ['name' => 'Litro', 'code' => 'L', 'symbol' => 'l', 'type' => 'volume'],
                ['name' => 'Caixa', 'code' => 'CX', 'symbol' => 'cx', 'type' => 'unit'],
                ['name' => 'Metro Quadrado', 'code' => 'M2', 'symbol' => 'm²', 'type' => 'area'],
            ];

            foreach ($defaultUnits as $unitData) {
                UnitOfMeasure::query()->firstOrCreate(
                    ['code' => $unitData['code'], 'tenant_id' => $tenant->id],
                    $unitData
                );
            }

            Warehouse::query()->firstOrCreate(
                ['code' => 'ALM-01', 'tenant_id' => $tenant->id],
                [
                    'name' => 'Almoxarifado Central',
                    'description' => 'Depósito principal de insumos e matérias-primas',
                    'is_active' => true,
                    'is_default' => true,
                ]
            );

            app(TenantContext::class)->clear();
        }

        // Reseta o contexto de team
        setPermissionsTeamId(null);
    }
}
