<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\KardexMovement;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Kardex Ledger Service
 *
 * Responsável pela escrituração imutável do Kardex de estoque e pelo cálculo
 * rigoroso do Custo Médio Ponderado (CMP) com bcmath (Zero Float) e locks pessimistas.
 */
class KardexLedgerService
{
    /**
     * Registra uma movimentação no razão imutável do Kardex e recalcula saldo e CMP.
     *
     * @param array{
     *     item_id: string,
     *     warehouse_id: string,
     *     movement_type: string,
     *     direction: 'in'|'out',
     *     quantity: string|numeric,
     *     unit_cost?: string|numeric|null,
     *     reference_type?: string|null,
     *     reference_id?: string|null,
     *     user_id?: string|null,
     *     notes?: string|null
     * } $data
     */
    public function recordMovement(array $data): KardexMovement
    {
        return DB::transaction(function () use ($data) {
            $itemId = $data['item_id'];
            $warehouseId = $data['warehouse_id'];
            $direction = $data['direction'];
            $qty = bcadd((string) $data['quantity'], '0', 4);

            if (bccomp($qty, '0.0000', 4) <= 0) {
                throw new InvalidArgumentException('A quantidade a movimentar deve ser estritamente maior que zero.');
            }

            if (! in_array($direction, ['in', 'out'], true)) {
                throw new InvalidArgumentException("Direção inválida: {$direction}. Use 'in' ou 'out'.");
            }

            /** @var Item $item */
            $item = Item::findOrFail($itemId);
            /** @var Warehouse $warehouse */
            $warehouse = Warehouse::findOrFail($warehouseId);

            /** @var InventoryBalance $balance */
            $balance = InventoryBalance::where('item_id', $itemId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = InventoryBalance::create([
                    'item_id' => $itemId,
                    'warehouse_id' => $warehouseId,
                    'quantity_on_hand' => '0.0000',
                    'quantity_reserved' => '0.0000',
                    'average_cost' => '0.0000',
                    'total_value' => '0.0000',
                ]);

                // Reobter com lock pessimista
                $balance = InventoryBalance::where('id', $balance->id)->lockForUpdate()->first();
            }

            $currQty = (string) $balance->quantity_on_hand;
            $currCmp = (string) $balance->average_cost;
            $currTotalVal = (string) $balance->total_value;

            if ($direction === 'out') {
                // Validação estrita de saldo físico negativo
                if (bccomp($qty, $currQty, 4) > 0) {
                    throw new InsufficientStockException(
                        $item->name,
                        $qty,
                        $currQty,
                        $warehouse->name
                    );
                }

                // Saídas saem rigorosamente ao Custo Médio Ponderado atual
                $unitCost = (isset($data['unit_cost']) && bccomp((string) $data['unit_cost'], '0.0000', 4) > 0)
                    ? bcadd((string) $data['unit_cost'], '0', 4)
                    : $currCmp;

                $totalCost = bcmul($qty, $unitCost, 4);
                $newQty = bcsub($currQty, $qty, 4);

                if (bccomp($newQty, '0.0000', 4) === 0) {
                    $newTotalVal = '0.0000';
                    $newCmp = $currCmp;
                } else {
                    $newTotalVal = bcsub($currTotalVal, $totalCost, 4);
                    // Prevenir discrepância residual por arredondamento
                    if (bccomp($newTotalVal, '0.0000', 4) < 0) {
                        $newTotalVal = '0.0000';
                    }
                    $newCmp = $currCmp;
                }
            } else {
                // Entrada: unit_cost é obrigatório
                $unitCost = bcadd((string) ($data['unit_cost'] ?? '0.0000'), '0', 4);
                $totalCost = bcmul($qty, $unitCost, 4);
                $newQty = bcadd($currQty, $qty, 4);
                $newTotalVal = bcadd($currTotalVal, $totalCost, 4);

                // Recálculo do Custo Médio Ponderado: (Valor Atual + Valor Entrada) / (Quantidade Atual + Quantidade Entrada)
                if (bccomp($newQty, '0.0000', 4) > 0) {
                    $newCmp = bcdiv($newTotalVal, $newQty, 4);
                } else {
                    $newCmp = $unitCost;
                }
            }

            // Atualização atômica do saldo
            $balance->update([
                'quantity_on_hand' => $newQty,
                'average_cost' => $newCmp,
                'total_value' => $newTotalVal,
                'last_movement_at' => now(),
            ]);

            // Se o item não tem custo padrão definido, inicializa com o novo CMP
            if (bccomp((string) $item->standard_cost, '0.0000', 4) === 0 && bccomp($newCmp, '0.0000', 4) > 0) {
                $item->update(['standard_cost' => $newCmp]);
            }

            // Inserção no Kardex Imutável
            return KardexMovement::create([
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'movement_type' => $data['movement_type'],
                'direction' => $direction,
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'balance_quantity_after' => $newQty,
                'balance_average_cost_after' => $newCmp,
                'balance_total_value_after' => $newTotalVal,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Reserva quantidade em estoque para Ordem de Produção ou Pedido.
     */
    public function reserveStock(string $itemId, string $warehouseId, string $quantity): void
    {
        DB::transaction(function () use ($itemId, $warehouseId, $quantity) {
            $qty = bcadd((string) $quantity, '0', 4);

            /** @var InventoryBalance $balance */
            $balance = InventoryBalance::where('item_id', $itemId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = InventoryBalance::create([
                    'item_id' => $itemId,
                    'warehouse_id' => $warehouseId,
                    'quantity_on_hand' => '0.0000',
                    'quantity_reserved' => '0.0000',
                    'average_cost' => '0.0000',
                    'total_value' => '0.0000',
                ]);
                $balance = InventoryBalance::where('id', $balance->id)->lockForUpdate()->first();
            }

            $available = bcsub((string) $balance->quantity_on_hand, (string) $balance->quantity_reserved, 4);

            if (bccomp($qty, $available, 4) > 0) {
                $item = Item::findOrFail($itemId);
                $warehouse = Warehouse::findOrFail($warehouseId);
                throw new InsufficientStockException(
                    $item->name,
                    $qty,
                    $available,
                    $warehouse->name
                );
            }

            $newReserved = bcadd((string) $balance->quantity_reserved, $qty, 4);
            $balance->update(['quantity_reserved' => $newReserved]);
        });
    }

    /**
     * Libera quantidade previamente reservada.
     */
    public function releaseReservedStock(string $itemId, string $warehouseId, string $quantity): void
    {
        DB::transaction(function () use ($itemId, $warehouseId, $quantity) {
            $qty = bcadd((string) $quantity, '0', 4);

            /** @var InventoryBalance $balance */
            $balance = InventoryBalance::where('item_id', $itemId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                return;
            }

            $newReserved = bcsub((string) $balance->quantity_reserved, $qty, 4);
            if (bccomp($newReserved, '0.0000', 4) < 0) {
                $newReserved = '0.0000';
            }

            $balance->update(['quantity_reserved' => $newReserved]);
        });
    }
}
