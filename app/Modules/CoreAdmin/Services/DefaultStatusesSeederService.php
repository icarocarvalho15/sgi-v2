<?php

declare(strict_types=1);

namespace App\Modules\CoreAdmin\Services;

use App\Core\Tenancy\TenantContext;
use App\Modules\CoreAdmin\Models\TenantStatus;
use Illuminate\Support\Facades\DB;

final class DefaultStatusesSeederService
{
    /**
     * Seeds the standard graphic arts and custom gifts operational statuses for a tenant.
     * Can be invoked during onboarding or tenant creation.
     */
    public static function seedForTenant(string $tenantId): void
    {
        $previousContext = TenantContext::id();

        try {
            TenantContext::set($tenantId);

            DB::transaction(function () use ($tenantId): void {
                $definitions = self::getDefaultDefinitions();

                foreach ($definitions as $module => $statuses) {
                    foreach ($statuses as $position => $statusData) {
                        TenantStatus::updateOrCreate(
                            [
                                'tenant_id' => $tenantId,
                                'module' => $module,
                                'slug' => $statusData['slug'],
                            ],
                            [
                                'name' => $statusData['name'],
                                'color' => $statusData['color'],
                                'position' => $position + 1,
                                'is_default' => $statusData['is_default'] ?? false,
                                'is_final' => $statusData['is_final'] ?? false,
                                'metadata' => $statusData['metadata'] ?? [],
                            ]
                        );
                    }
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
     * Returns default status catalog tailored for the Printing & Custom Gifts sector.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function getDefaultDefinitions(): array
    {
        return [
            'quotes' => [
                [
                    'name' => 'Rascunho Técnico',
                    'slug' => 'rascunho-tecnico',
                    'color' => '#94A3B8',
                    'is_default' => true,
                    'is_final' => false,
                ],
                [
                    'name' => 'Aguardando Aprovação do Cliente',
                    'slug' => 'aguardando-aprovacao',
                    'color' => '#38BDF8',
                    'is_default' => false,
                    'is_final' => false,
                ],
                [
                    'name' => 'Orçamento Aprovado',
                    'slug' => 'orcamento-aprovado',
                    'color' => '#22C55E',
                    'is_default' => false,
                    'is_final' => true,
                ],
                [
                    'name' => 'Rejeitado / Cancelado',
                    'slug' => 'rejeitado-cancelado',
                    'color' => '#EF4444',
                    'is_default' => false,
                    'is_final' => true,
                ],
            ],
            'production_orders' => [
                [
                    'name' => 'Aguardando Arquivo / Pré-Impressão',
                    'slug' => 'pre-impressao',
                    'color' => '#F59E0B',
                    'is_default' => true,
                    'is_final' => false,
                ],
                [
                    'name' => 'Gravação de Chapas CTP / Fotolitos',
                    'slug' => 'gravacao-ctp',
                    'color' => '#6366F1',
                    'is_default' => false,
                    'is_final' => false,
                ],
                [
                    'name' => 'Em Impressão (Offset / Digital)',
                    'slug' => 'em-impressao',
                    'color' => '#3B82F6',
                    'is_default' => false,
                    'is_final' => false,
                ],
                [
                    'name' => 'Personalização (Silk / Laser / Sublimação)',
                    'slug' => 'personalizacao-brindes',
                    'color' => '#EC4899',
                    'is_default' => false,
                    'is_final' => false,
                ],
                [
                    'name' => 'Acabamento & Guilhotina',
                    'slug' => 'acabamento-guilhotina',
                    'color' => '#8B5CF6',
                    'is_default' => false,
                    'is_final' => false,
                ],
                [
                    'name' => 'Controle de Qualidade & Embalagem',
                    'slug' => 'controle-qualidade',
                    'color' => '#14B8A6',
                    'is_default' => false,
                    'is_final' => false,
                ],
                [
                    'name' => 'Pronto para Expedição',
                    'slug' => 'pronto-expedicao',
                    'color' => '#10B981',
                    'is_default' => false,
                    'is_final' => true,
                ],
            ],
            'sales_kanban' => [
                [
                    'name' => 'Lead / Contato Inicial',
                    'slug' => 'lead-contato',
                    'color' => '#94A3B8',
                    'is_default' => true,
                    'is_final' => false,
                ],
                [
                    'name' => 'Briefing & Especificação Técnica',
                    'slug' => 'briefing-tecnico',
                    'color' => '#38BDF8',
                    'is_default' => false,
                    'is_final' => false,
                ],
                [
                    'name' => 'Proposta Técnica Enviada',
                    'slug' => 'proposta-enviada',
                    'color' => '#F59E0B',
                    'is_default' => false,
                    'is_final' => false,
                ],
                [
                    'name' => 'Negociação & Fechamento',
                    'slug' => 'negociacao',
                    'color' => '#8B5CF6',
                    'is_default' => false,
                    'is_final' => false,
                ],
                [
                    'name' => 'Venda Fechada (Gerou Pedido/OP)',
                    'slug' => 'venda-fechada',
                    'color' => '#22C55E',
                    'is_default' => false,
                    'is_final' => true,
                ],
                [
                    'name' => 'Oportunidade Perdida',
                    'slug' => 'perdido',
                    'color' => '#EF4444',
                    'is_default' => false,
                    'is_final' => true,
                ],
            ],
        ];
    }
}
