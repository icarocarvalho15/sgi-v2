<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Core\Tenancy\Exceptions\TenantContextMissingException;
use App\Core\Tenancy\TenantContext;
use App\Modules\Inventory\DTOs\StockMovementDTO;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\KardexMovement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class KardexLedgerService
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * Registra atomicamente uma movimentação no Kardex imutável e atualiza o saldo derivado.
     * Utiliza Cache Lock distribuído + Pessimistic Lock (lockForUpdate) e cálculo de alta precisão em bcmath.
     */
    public function record(StockMovementDTO $dto): KardexMovement
    {
        $tenantId = $this->tenantContext->id();

        if ($tenantId === null) {
            throw new TenantContextMissingException('Não é possível registrar movimentação de estoque sem tenant ativo.');
        }

        $lockKey = "tenant:{$tenantId}:item_stock:{$dto->itemId}:warehouse:{$dto->warehouseId}";

        return Cache::lock($lockKey, 10)->block(5, function () use ($tenantId, $dto): KardexMovement {
            return DB::transaction(function () use ($tenantId, $dto): KardexMovement {
                if (DB::connection()->getDriverName() === 'pgsql') {
                    DB::statement("SELECT set_config('app.current_tenant_id', ?, true)", [$tenantId]);
                }

                /** @var Item $item */
                $item = Item::where('id', $dto->itemId)->lockForUpdate()->firstOrFail();

                /** @var InventoryBalance|null $balance */
                $balance = InventoryBalance::where('warehouse_id', $dto->warehouseId)
                    ->where('item_id', $dto->itemId)
                    ->lockForUpdate()
                    ->first();

                $previousBalance = $balance ? (string) $balance->quantity_on_hand : '0.0000';
                $previousAvgCost = $balance ? (string) $balance->average_cost : ((string) $item->average_cost ?: '0.0000');

                if ($dto->movementType->isInbound()) {
                    $unitCost = $dto->unitCost !== null && bccomp($dto->unitCost, '0', 4) > 0
                        ? $dto->unitCost
                        : $previousAvgCost;

                    $resultingBalance = bcadd($previousBalance, $dto->quantity, 4);
                    $movementTotalCost = bcmul($dto->quantity, $unitCost, 4);

                    // Recalcula Custo Médio Ponderado (CMP)
                    if (bccomp($resultingBalance, '0', 4) > 0) {
                        $previousTotalValue = bcmul($previousBalance, $previousAvgCost, 4);
                        $newTotalValue = bcadd($previousTotalValue, $movementTotalCost, 4);
                        $resultingAvgCost = bcdiv($newTotalValue, $resultingBalance, 4);
                    } else {
                        $resultingAvgCost = $unitCost;
                    }

                    // Atualiza último custo e CMP do cadastro mestre do item
                    $item->update([
                        'average_cost' => $resultingAvgCost,
                        'last_cost' => $unitCost,
                    ]);
                } else {
                    $resultingBalance = bcsub($previousBalance, $dto->quantity, 4);

                    // Validação de estoque negativo conforme política industrial do item
                    if (! $item->allow_negative_stock && bccomp($resultingBalance, '0', 4) < 0) {
                        throw new InsufficientStockException(
                            $item->id,
                            $dto->warehouseId,
                            $dto->quantity,
                            $previousBalance
                        );
                    }

                    // Saídas consomem ao Custo Médio Ponderado atual
                    $unitCost = $dto->unitCost !== null && bccomp($dto->unitCost, '0', 4) > 0
                        ? $dto->unitCost
                        : $previousAvgCost;

                    $movementTotalCost = bcmul($dto->quantity, $unitCost, 4);
                    $resultingAvgCost = $previousAvgCost;
                }

                $movementNumber = $this->generateMovementNumber();

                /** @var KardexMovement $movement */
                $movement = KardexMovement::create([
                    'movement_number' => $movementNumber,
                    'warehouse_id' => $dto->warehouseId,
                    'item_id' => $dto->itemId,
                    'movement_type' => $dto->movementType,
                    'quantity' => $dto->quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $movementTotalCost,
                    'previous_balance' => $previousBalance,
                    'resulting_balance' => $resultingBalance,
                    'previous_average_cost' => $previousAvgCost,
                    'resulting_average_cost' => $resultingAvgCost,
                    'reference_type' => $dto->referenceType,
                    'reference_id' => $dto->referenceId,
                    'document_number' => $dto->documentNumber,
                    'justification' => $dto->justification,
                    'user_id' => $dto->userId ?? auth()->id(),
                ]);

                if ($balance) {
                    $balance->update([
                        'quantity_on_hand' => $resultingBalance,
                        'average_cost' => $resultingAvgCost,
                        'last_movement_at' => now(),
                    ]);
                } else {
                    InventoryBalance::create([
                        'warehouse_id' => $dto->warehouseId,
                        'item_id' => $dto->itemId,
                        'quantity_on_hand' => $resultingBalance,
                        'quantity_reserved' => '0.0000',
                        'average_cost' => $resultingAvgCost,
                        'last_movement_at' => now(),
                    ]);
                }

                return $movement;
            });
        });
    }

    private function generateMovementNumber(): string
    {
        return sprintf(
            'KDX-%s-%s',
            now()->format('Ymd'),
            strtoupper(substr(bin2hex(random_bytes(3)), 0, 6))
        );
    }
}
