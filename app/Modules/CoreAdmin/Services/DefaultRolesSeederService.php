<?php

declare(strict_types=1);

namespace App\Modules\CoreAdmin\Services;

use App\Core\Tenancy\TenantContext;
use App\Modules\CoreAdmin\Models\TenantRole;
use Illuminate\Support\Facades\DB;

final class DefaultRolesSeederService
{
    /**
     * Seeds the standard roles and permission sets for a specific tenant.
     */
    public static function seedForTenant(string $tenantId): void
    {
        $previousContext = TenantContext::id();

        try {
            TenantContext::set($tenantId);

            DB::transaction(function () use ($tenantId): void {
                $definitions = self::getDefaultRoleDefinitions();

                foreach ($definitions as $roleData) {
                    TenantRole::updateOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'slug' => $roleData['slug'],
                        ],
                        [
                            'name' => $roleData['name'],
                            'description' => $roleData['description'],
                            'color' => $roleData['color'],
                            'permissions' => $roleData['permissions'],
                            'is_system' => $roleData['is_system'] ?? false,
                        ]
                    );
                }
            });
        } finally {
            if ($previousContext !== null) {
                TenantContext::set($previousContext);
            } else {
                TenantContext::clear();
            }
        }
    }

    /**
     * Returns default role templates for printing and custom gift companies.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getDefaultRoleDefinitions(): array
    {
        return [
            [
                'name' => 'Dono / Administrador Geral',
                'slug' => 'admin',
                'description' => 'Acesso total irrestrito a todos os módulos, financeiro confidencial, DRE, PCP e configurações.',
                'color' => '#6366F1',
                'permissions' => [
                    'crm',
                    'quotes',
                    'boms',
                    'production',
                    'inventory',
                    'kardex',
                    'finance',
                    'reports',
                    'settings_fiscal',
                    'settings_admin',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Vendedor / Comercial',
                'slug' => 'sales',
                'description' => 'Focado na captação de clientes, elaboração de orçamentos técnicos gráficos e funil de vendas.',
                'color' => '#0EA5E9',
                'permissions' => [
                    'crm',
                    'quotes',
                    'inventory',
                ],
                'is_system' => false,
            ],
            [
                'name' => 'Chão de Fábrica / PCP',
                'slug' => 'production',
                'description' => 'Acesso à engenharia gráfica, fichas técnicas (BOM), execução de ordens de produção e controle de estoque.',
                'color' => '#F59E0B',
                'permissions' => [
                    'boms',
                    'production',
                    'inventory',
                    'kardex',
                ],
                'is_system' => false,
            ],
            [
                'name' => 'Financeiro / Fiscal',
                'slug' => 'finance',
                'description' => 'Gestão de contas a pagar/receber, conciliação, fluxo de caixa, DRE gerencial e parâmetros fiscais/NFe.',
                'color' => '#10B981',
                'permissions' => [
                    'crm',
                    'finance',
                    'reports',
                    'settings_fiscal',
                ],
                'is_system' => false,
            ],
        ];
    }

    /**
     * Master catalog of system modules and their metadata.
     *
     * @return array<string, array<string, string>>
     */
    public static function getAvailableModules(): array
    {
        return [
            'crm' => [
                'name' => 'Clientes & Fornecedores',
                'description' => 'Cadastro comercial de clientes, contatos, agências e múltiplos endereços.',
            ],
            'quotes' => [
                'name' => 'Orçamentos Técnicos & Funil',
                'description' => 'Cálculo técnico de custos gráficos, margens, propostas e quadro Kanban de vendas.',
            ],
            'boms' => [
                'name' => 'Fichas Técnicas de Produto (BOM)',
                'description' => 'Engenharia de produtos gráficos: composição de substratos, chapas CTP, tintas e insumos.',
            ],
            'production' => [
                'name' => 'Chão de Fábrica & OPs (PCP)',
                'description' => 'Acompanhamento de ordens de produção, apontamento de horas e centros de trabalho.',
            ],
            'inventory' => [
                'name' => 'Estoque & Saldos',
                'description' => 'Consulta de disponibilidade de saldo em tempo real nos almoxarifados da gráfica.',
            ],
            'kardex' => [
                'name' => 'Razão Kardex & Ajustes',
                'description' => 'Extrato contábil imutável, custo médio ponderado e movimentações manuais de insumos.',
            ],
            'finance' => [
                'name' => 'Financeiro (Títulos)',
                'description' => 'Gestão de contas a pagar e receber, baixas parciais, juros e multas.',
            ],
            'reports' => [
                'name' => 'Fluxo de Caixa & DRE',
                'description' => 'Demonstrativo de Resultado do Exercício e projeção financeira da empresa.',
            ],
            'settings_fiscal' => [
                'name' => 'Parâmetros Fiscais & Gateways',
                'description' => 'Configurações de emissão de NFe/NFSe, certificado A1 e meios de pagamento.',
            ],
        ];
    }
}
