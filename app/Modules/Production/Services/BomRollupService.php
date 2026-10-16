<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Modules\Inventory\Models\Item;
use App\Modules\Production\Exceptions\CircularBomDependencyException;
use App\Modules\Production\Models\Bom;
use App\Modules\Production\Models\BomItem;
use Illuminate\Support\Facades\DB;

final class BomRollupService
{
    /**
     * Valida ausência de loops circulares e calcula o Rollup de Custo Estimado da BOM com precisão bcmath.
     */
    public function calculate(Bom $bom): Bom
    {
        return DB::transaction(function () use ($bom): Bom {
            $bom->load(['items.item', 'items.unitOfMeasure']);

            // 1. Verificação de dependência circular
            $this->validateNoCircularDependency($bom->item_id, $bom->items->pluck('item_id')->all());

            $totalEstimatedCost = '0.0000';

            /** @var BomItem $bomItem */
            foreach ($bom->items as $bomItem) {
                $componentItem = $bomItem->item;

                $unitCost = $this->resolveComponentUnitCost($componentItem);

                // Aplica taxa de refugo / perda: quantity * (1 + scrap_rate / 100)
                $scrapRate = (string) ($bomItem->scrap_rate_percentage ?? '0.00');
                $scrapFactor = bcadd('1', bcdiv($scrapRate, '100', 4), 4);
                $effectiveQty = bcmul((string) $bomItem->quantity, $scrapFactor, 4);

                $componentTotalCost = bcmul($effectiveQty, $unitCost, 4);

                $bomItem->update([
                    'unit_cost' => $unitCost,
                    'total_cost' => $componentTotalCost,
                ]);

                $totalEstimatedCost = bcadd($totalEstimatedCost, $componentTotalCost, 4);
            }

            $bom->update([
                'total_estimated_cost' => $totalEstimatedCost,
            ]);

            return $bom->fresh(['items.item', 'items.unitOfMeasure']);
        });
    }

    /**
     * Valida recursivamente se algum componente gera um loop de volta para o item pai.
     *
     * @param  array<int, string>  $componentItemIds
     * @param  array<int, string>  $visited
     */
    public function validateNoCircularDependency(string $parentItemId, array $componentItemIds, array $visited = []): void
    {
        $visited[] = $parentItemId;

        foreach ($componentItemIds as $childItemId) {
            if ($childItemId === $parentItemId || in_array($childItemId, $visited, true)) {
                throw new CircularBomDependencyException($parentItemId, $childItemId);
            }

            /** @var Item|null $childItem */
            $childItem = Item::find($childItemId);
            if ($childItem && $childItem->is_manufactured) {
                // Busca a BOM padrão ativa do subconjunto
                $childBom = Bom::where('item_id', $childItemId)
                    ->where('is_active', true)
                    ->with('items')
                    ->first();

                if ($childBom && $childBom->items->isNotEmpty()) {
                    $this->validateNoCircularDependency(
                        $parentItemId,
                        $childBom->items->pluck('item_id')->all(),
                        $visited
                    );
                }
            }
        }
    }

    private function resolveComponentUnitCost(Item $componentItem): string
    {
        // Se o item for manufaturado e possuir BOM padrão ativa, usa o custo unitário estimado do lote
        if ($componentItem->is_manufactured) {
            $subBom = Bom::where('item_id', $componentItem->id)
                ->where('is_active', true)
                ->first();

            if ($subBom && bccomp((string) $subBom->total_estimated_cost, '0', 4) > 0 && bccomp((string) $subBom->batch_size, '0', 4) > 0) {
                return bcdiv((string) $subBom->total_estimated_cost, (string) $subBom->batch_size, 4);
            }
        }

        // Caso contrário, adota o Custo Médio Ponderado ou Último Custo do item
        $avgCost = (string) $componentItem->average_cost;
        if (bccomp($avgCost, '0', 4) > 0) {
            return $avgCost;
        }

        $lastCost = (string) $componentItem->last_cost;
        if (bccomp($lastCost, '0', 4) > 0) {
            return $lastCost;
        }

        return '0.0000';
    }
}
