<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Core\Tenancy\Exceptions\TenantContextMissingException;
use App\Core\Tenancy\TenantContext;
use App\Modules\Inventory\DTOs\StockMovementDTO;
use App\Modules\Inventory\Enums\KardexMovementType;
use App\Modules\Inventory\Services\KardexLedgerService;
use App\Modules\Production\DTOs\CreateProductionOrderDTO;
use App\Modules\Production\DTOs\RecordProductionDTO;
use App\Modules\Production\Enums\OperationStatus;
use App\Modules\Production\Enums\ProductionOrderStatus;
use App\Modules\Production\Exceptions\InvalidProductionStateException;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Production\Models\ProductionOrderMaterial;
use App\Modules\Production\Models\ProductionOrderOperation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class ProductionOrderService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly KardexLedgerService $kardexLedgerService,
    ) {}

    public function create(CreateProductionOrderDTO $dto): ProductionOrder
    {
        $orderNumber = $this->generateOrderNumber();

        return ProductionOrder::create([
            'order_number' => $orderNumber,
            'item_id' => $dto->itemId,
            'bom_id' => $dto->bomId,
            'warehouse_id' => $dto->warehouseId,
            'materials_warehouse_id' => $dto->materialsWarehouseId,
            'planned_quantity' => $dto->plannedQuantity,
            'priority' => $dto->priority,
            'status' => ProductionOrderStatus::Draft,
            'start_date' => $dto->startDate,
            'due_date' => $dto->dueDate,
            'notes' => $dto->notes,
        ]);
    }

    public function release(ProductionOrder $order): ProductionOrder
    {
        if (! $order->status->canRelease()) {
            throw new InvalidProductionStateException(
                "A Ordem de Produção {$order->order_number} não pode ser liberada pois está no status {$order->status->label()}."
            );
        }

        return DB::transaction(function () use ($order): ProductionOrder {
            $order->load(['bom.items', 'bom.routingSteps']);
            $bom = $order->bom;

            if (! $bom) {
                throw new InvalidProductionStateException("A OP {$order->order_number} não possui uma Ficha Técnica associada.");
            }

            // 1. Gera empenho de matérias-primas
            $batchSize = bccomp((string) $bom->batch_size, '0', 4) > 0 ? (string) $bom->batch_size : '1.0000';
            $factor = bcdiv((string) $order->planned_quantity, $batchSize, 4);

            foreach ($bom->items as $bomItem) {
                $baseQty = bcmul((string) $bomItem->quantity, $factor, 4);
                $scrapRate = (string) ($bomItem->scrap_rate_percentage ?? '0.00');
                $scrapFactor = bcadd('1', bcdiv($scrapRate, '100', 4), 4);
                $plannedMaterialQty = bcmul($baseQty, $scrapFactor, 4);

                ProductionOrderMaterial::create([
                    'production_order_id' => $order->id,
                    'item_id' => $bomItem->item_id,
                    'unit_of_measure_id' => $bomItem->unit_of_measure_id,
                    'planned_quantity' => $plannedMaterialQty,
                    'consumed_quantity' => '0.0000',
                    'unit_cost' => $bomItem->unit_cost ?: '0.0000',
                    'total_cost' => '0.0000',
                ]);
            }

            // 2. Gera operações a partir do roteiro da BOM
            foreach ($bom->routingSteps as $step) {
                ProductionOrderOperation::create([
                    'production_order_id' => $order->id,
                    'routing_step_id' => $step->id,
                    'work_center_id' => $step->work_center_id,
                    'sequence' => $step->sequence,
                    'status' => OperationStatus::Pending,
                ]);
            }

            $order->update([
                'status' => ProductionOrderStatus::Released,
            ]);

            return $order->fresh(['materials.item', 'operations.workCenter']);
        });
    }

    public function startOperation(ProductionOrderOperation $operation, ?string $userId): ProductionOrderOperation
    {
        return DB::transaction(function () use ($operation, $userId): ProductionOrderOperation {
            $operation->update([
                'status' => OperationStatus::InProgress,
                'started_at' => now(),
                'operator_user_id' => $userId,
            ]);

            $order = $operation->productionOrder;
            if ($order && $order->status === ProductionOrderStatus::Released) {
                $order->update([
                    'status' => ProductionOrderStatus::InProgress,
                    'actual_start_at' => $order->actual_start_at ?? now(),
                ]);
            }

            return $operation->fresh();
        });
    }

    public function pauseOperation(ProductionOrderOperation $operation): ProductionOrderOperation
    {
        return DB::transaction(function () use ($operation): ProductionOrderOperation {
            $elapsedSeconds = 0;
            if ($operation->started_at) {
                $elapsedSeconds = (int) abs(now()->diffInSeconds(Carbon::parse($operation->started_at)));
            }

            $newTotalSeconds = $operation->executed_time_seconds + $elapsedSeconds;

            $operation->update([
                'status' => OperationStatus::Paused,
                'paused_at' => now(),
                'executed_time_seconds' => $newTotalSeconds,
            ]);

            return $operation->fresh();
        });
    }

    public function completeOperation(ProductionOrderOperation $operation): ProductionOrderOperation
    {
        return DB::transaction(function () use ($operation): ProductionOrderOperation {
            $elapsedSeconds = 0;
            if ($operation->status === OperationStatus::InProgress && $operation->started_at) {
                $elapsedSeconds = (int) abs(now()->diffInSeconds(Carbon::parse($operation->started_at)));
            }

            $newTotalSeconds = $operation->executed_time_seconds + $elapsedSeconds;

            $operation->update([
                'status' => OperationStatus::Completed,
                'completed_at' => now(),
                'executed_time_seconds' => $newTotalSeconds,
            ]);

            return $operation->fresh();
        });
    }

    public function recordProduction(RecordProductionDTO $dto): ProductionOrder
    {
        $tenantId = $this->tenantContext->id();
        if ($tenantId === null) {
            throw new TenantContextMissingException('Tenant ativo ausente ao apontar produção.');
        }

        $lockKey = "tenant:{$tenantId}:production_order:{$dto->productionOrderId}";

        return Cache::lock($lockKey, 15)->block(5, function () use ($dto): ProductionOrder {
            return DB::transaction(function () use ($dto): ProductionOrder {
                /** @var ProductionOrder $order */
                $order = ProductionOrder::with(['materials.item', 'bom', 'item'])
                    ->where('id', $dto->productionOrderId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $order->status->canProduce()) {
                    throw new InvalidProductionStateException(
                        "Não é possível registrar produção na OP {$order->order_number} com status {$order->status->label()}."
                    );
                }

                $plannedQty = (string) $order->planned_quantity;
                $producedQty = $dto->producedQuantity;

                if (bccomp($producedQty, '0', 4) <= 0) {
                    throw new InvalidProductionStateException('A quantidade produzida informada deve ser maior que zero.');
                }

                // Proporção produzida neste apontamento
                $proportion = bccomp($plannedQty, '0', 4) > 0
                    ? bcdiv($producedQty, $plannedQty, 6)
                    : '1.000000';

                $batchMaterialsCost = '0.0000';

                // 1. Baixa proporcional de matérias-primas no Kardex (OUT_PRODUCTION)
                foreach ($order->materials as $material) {
                    $qtyToConsume = bcmul((string) $material->planned_quantity, $proportion, 4);

                    if (bccomp($qtyToConsume, '0', 4) > 0) {
                        $movement = $this->kardexLedgerService->record(new StockMovementDTO(
                            warehouseId: $order->materials_warehouse_id,
                            itemId: $material->item_id,
                            movementType: KardexMovementType::OutProduction,
                            quantity: $qtyToConsume,
                            unitCost: null, // Consome ao Custo Médio Ponderado atual do depósito
                            referenceType: 'production_orders',
                            referenceId: $order->id,
                            documentNumber: $order->order_number,
                            justification: "Consumo de insumo na OP {$order->order_number}",
                            userId: $dto->userId,
                        ));

                        $batchMaterialsCost = bcadd($batchMaterialsCost, (string) $movement->total_cost, 4);

                        $newConsumed = bcadd((string) $material->consumed_quantity, $qtyToConsume, 4);
                        $newTotalCost = bcadd((string) $material->total_cost, (string) $movement->total_cost, 4);

                        $material->update([
                            'consumed_quantity' => $newConsumed,
                            'total_cost' => $newTotalCost,
                        ]);
                    }
                }

                // 2. Calcula custo unitário do produto acabado produzido
                $finishedGoodUnitCost = bcdiv($batchMaterialsCost, $producedQty, 4);
                if (bccomp($finishedGoodUnitCost, '0', 4) <= 0) {
                    $finishedGoodUnitCost = (string) ($order->bom?->total_estimated_cost ?: $order->item->average_cost);
                }

                // 3. Entrada do Produto Acabado no Estoque (IN_PRODUCTION)
                $this->kardexLedgerService->record(new StockMovementDTO(
                    warehouseId: $order->warehouse_id,
                    itemId: $order->item_id,
                    movementType: KardexMovementType::InProduction,
                    quantity: $producedQty,
                    unitCost: $finishedGoodUnitCost,
                    referenceType: 'production_orders',
                    referenceId: $order->id,
                    documentNumber: $order->order_number,
                    justification: "Entrada de produto acabado gerado pela OP {$order->order_number}",
                    userId: $dto->userId,
                ));

                // 4. Atualiza os saldos produzidos e scrap da OP
                $newProduced = bcadd((string) $order->produced_quantity, $producedQty, 4);
                $newScrapped = bcadd((string) $order->scrapped_quantity, $dto->scrappedQuantity, 4);
                $totalRealized = bcadd($newProduced, $newScrapped, 4);

                $newStatus = $order->status;
                $actualEnd = null;

                if (bccomp($totalRealized, $plannedQty, 4) >= 0) {
                    $newStatus = ProductionOrderStatus::Completed;
                    $actualEnd = now();
                } elseif ($order->status === ProductionOrderStatus::Released) {
                    $newStatus = ProductionOrderStatus::InProgress;
                }

                $order->update([
                    'produced_quantity' => $newProduced,
                    'scrapped_quantity' => $newScrapped,
                    'status' => $newStatus,
                    'actual_start_at' => $order->actual_start_at ?? now(),
                    'actual_end_at' => $actualEnd ?? $order->actual_end_at,
                ]);

                return $order->fresh(['materials.item', 'operations.workCenter', 'item', 'warehouse']);
            });
        });
    }

    private function generateOrderNumber(): string
    {
        return sprintf(
            'OP-%s-%s',
            now()->format('Ymd'),
            strtoupper(substr(bin2hex(random_bytes(3)), 0, 5))
        );
    }
}
