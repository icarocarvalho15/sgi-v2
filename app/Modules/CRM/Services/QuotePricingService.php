<?php

declare(strict_types=1);

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Models\Quote;
use App\Modules\Inventory\Models\Item;
use App\Modules\Production\Models\Bom;

final class QuotePricingService
{
    /**
     * @param array{
     *     item_id: string,
     *     bom_id?: string|null,
     *     quantity: string|numeric,
     *     markup_percentage?: string|numeric
     * } $data
     * @return array{
     *     unit_cost: string,
     *     unit_price: string,
     *     total_price: string,
     *     markup_percentage: string,
     *     margin_percentage: string,
     *     materials_cost: string,
     *     operations_cost: string,
     *     lead_time_days: int
     * }
     */
    public function calculateItemPricing(array $data): array
    {
        $itemId = (string) $data['item_id'];
        $bomId = isset($data['bom_id']) && $data['bom_id'] !== '' ? (string) $data['bom_id'] : null;
        $quantity = bccomp((string) $data['quantity'], '0', 4) > 0 ? (string) $data['quantity'] : '1.0000';
        $markup = isset($data['markup_percentage']) ? (string) $data['markup_percentage'] : '30.00';

        $item = Item::find($itemId);
        $bom = $bomId ? Bom::with(['items', 'routingSteps.workCenter'])->find($bomId) : null;

        $materialsCost = '0.0000';
        $operationsCost = '0.0000';
        $leadTimeDays = 5; // Prazo base

        if ($bom) {
            $batchSize = bccomp((string) $bom->batch_size, '0', 4) > 0 ? (string) $bom->batch_size : '1.0000';

            // Custo dos Insumos (Materiais)
            $bomMaterialsSum = '0.0000';
            foreach ($bom->items as $bItem) {
                $itemCost = bcmul((string) $bItem->quantity, (string) ($bItem->unit_cost ?: '0.0000'), 4);
                $scrapFactor = bcadd('1', bcdiv((string) ($bItem->scrap_rate_percentage ?: '0.00'), '100', 4), 4);
                $bomMaterialsSum = bcadd($bomMaterialsSum, bcmul($itemCost, $scrapFactor, 4), 4);
            }
            $materialsCost = bcdiv($bomMaterialsSum, $batchSize, 4);

            // Custo Operacional (Centros de Trabalho)
            $totalMinutes = '0.00';
            $bomOpsSum = '0.0000';
            foreach ($bom->routingSteps as $step) {
                $hourlyCost = (string) ($step->workCenter?->hourly_cost ?: '0.0000');
                $setupTime = (string) ($step->setup_time_minutes ?: '0.00');
                $runTime = (string) ($step->run_time_minutes_per_unit ?: '0.00');

                $stepMinutesPerUnit = bcadd(bcdiv($setupTime, $batchSize, 4), $runTime, 4);
                $totalMinutes = bcadd($totalMinutes, $stepMinutesPerUnit, 2);

                $stepHours = bcdiv($stepMinutesPerUnit, '60', 4);
                $stepCost = bcmul($stepHours, $hourlyCost, 4);
                $bomOpsSum = bcadd($bomOpsSum, $stepCost, 4);
            }
            $operationsCost = $bomOpsSum;

            // Prazo baseado em horas totais (8h diárias de capacidade padrão)
            $totalHours = bcdiv(bcmul($totalMinutes, $quantity, 2), '60', 2);
            $calculatedDays = (int) ceil((float) bcdiv($totalHours, '8.00', 2)) + 2; // + 2 dias de setup/expedição
            $leadTimeDays = max(3, $calculatedDays);
        } elseif ($item) {
            $materialsCost = (string) ($item->default_cost ?: '0.0000');
            $operationsCost = '0.0000';
        }

        $unitCost = bcadd($materialsCost, $operationsCost, 4);

        // Preço Unitário = Custo * (1 + Markup / 100)
        $markupMultiplier = bcadd('1', bcdiv($markup, '100', 4), 4);
        $unitPrice = bcmul($unitCost, $markupMultiplier, 4);

        // Se custo for 0, garantir que o preço seja pelo menos o cadastrado no item
        if (bccomp($unitPrice, '0.0000', 4) === 0 && $item && bccomp((string) $item->default_price, '0.0000', 4) > 0) {
            $unitPrice = (string) $item->default_price;
        }

        $totalPrice = bcmul($unitPrice, $quantity, 2);

        // Margem = ((Preço - Custo) / Preço) * 100
        $margin = '0.00';
        if (bccomp($unitPrice, '0.0000', 4) > 0) {
            $diff = bcsub($unitPrice, $unitCost, 4);
            $margin = number_format(((float) $diff / (float) $unitPrice) * 100, 2, '.', '');
        }

        return [
            'unit_cost' => $unitCost,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'markup_percentage' => bcadd($markup, '0.00', 2),
            'margin_percentage' => $margin,
            'materials_cost' => $materialsCost,
            'operations_cost' => $operationsCost,
            'lead_time_days' => $leadTimeDays,
        ];
    }

    /**
     * Recalcula e persiste totais de um orçamento existente.
     */
    public function recalculateQuote(Quote $quote): Quote
    {
        $quote->load('items.item', 'items.bom');

        $totalMaterials = '0.0000';
        $totalOperations = '0.0000';
        $finalTotal = '0.00';
        $maxLeadTime = 0;

        foreach ($quote->items as $item) {
            $pricing = $this->calculateItemPricing([
                'item_id' => $item->item_id,
                'bom_id' => $item->bom_id,
                'quantity' => $item->quantity,
                'markup_percentage' => $item->markup_percentage,
            ]);

            $item->update([
                'unit_cost' => $pricing['unit_cost'],
                'unit_price' => $pricing['unit_price'],
                'total_price' => $pricing['total_price'],
                'lead_time_days' => $pricing['lead_time_days'],
            ]);

            $totalMaterials = bcadd($totalMaterials, bcmul($pricing['materials_cost'], (string) $item->quantity, 4), 4);
            $totalOperations = bcadd($totalOperations, bcmul($pricing['operations_cost'], (string) $item->quantity, 4), 4);
            $finalTotal = bcadd($finalTotal, $pricing['total_price'], 2);
            $maxLeadTime = max($maxLeadTime, $pricing['lead_time_days']);
        }

        $totalCost = bcadd($totalMaterials, $totalOperations, 4);
        $overallMargin = '0.00';
        $overallMarkup = '0.00';

        if (bccomp($finalTotal, '0.00', 2) > 0) {
            $diff = bcsub($finalTotal, $totalCost, 4);
            $overallMargin = number_format(((float) $diff / (float) $finalTotal) * 100, 2, '.', '');
        }

        if (bccomp($totalCost, '0.0000', 4) > 0) {
            $diff = bcsub($finalTotal, $totalCost, 4);
            $overallMarkup = number_format(((float) $diff / (float) $totalCost) * 100, 2, '.', '');
        }

        $quote->update([
            'total_materials_cost' => $totalMaterials,
            'total_operations_cost' => $totalOperations,
            'markup_percentage' => $overallMarkup,
            'margin_percentage' => $overallMargin,
            'final_total' => $finalTotal,
            'lead_time_days' => max(1, $maxLeadTime),
        ]);

        return $quote->fresh(['items.item', 'items.bom', 'partner', 'deal']);
    }
}
