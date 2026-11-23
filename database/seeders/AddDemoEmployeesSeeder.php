<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AddDemoEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            return;
        }

        User::where('email', 'operador@express.com')->update([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $users = [
            [
                'email' => 'vendas@express.com',
                'name' => 'Lucas Santos (Vendas)',
                'password' => bcrypt('Vendas123!'),
                'role' => 'sales',
            ],
            [
                'email' => 'producao@express.com',
                'name' => 'Marcos Impressor (PCP)',
                'password' => bcrypt('Producao123!'),
                'role' => 'production',
            ],
            [
                'email' => 'financeiro@express.com',
                'name' => 'Beatriz Costa (Financeiro)',
                'password' => bcrypt('Financeiro123!'),
                'role' => 'finance',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'tenant_id' => $tenant->id,
                    'role' => $userData['role'],
                    'is_active' => true,
                    'is_super_admin' => false,
                ]
            );
        }
    }
}
