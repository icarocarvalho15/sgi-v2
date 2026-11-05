<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\ItemCategory;
use App\Modules\Inventory\Models\KardexMovement;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\KardexLedgerService;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Production\Models\ProductionOrderMaterial;
use Illuminate\Support\Facades\DB;

class ConsumeProductionOrderMaterialsAction
{
    public function __construct(
        protected KardexLedgerService $kardexService
    ) {}

    /**
     * Dá baixa de consumo de um insumo vinculado à Ordem de Produção no Kardex.
     */
    public function execute(
        ProductionOrder $productionOrder,
        ProductionOrderMaterial $material,
        string $quantityToConsume,
        ?string $warehouseId = null,
        ?string $userId = null
    ): KardexMovement {
        return DB::transaction(function () use ($productionOrder, $material, $quantityToConsume, $warehouseId, $userId) {
            // Depósito padrão se não informado
            if (! $warehouseId) {
                /** @var Warehouse|null $defaultWarehouse */
                $defaultWarehouse = Warehouse::where('is_default', true)->first()
                    ?? Warehouse::first();

                if (! $defaultWarehouse) {
                    $defaultWarehouse = Warehouse::create([
                        'code' => 'ALMOX-GERAL',
                        'name' => 'Almoxarifado Geral',
                        'is_default' => true,
                    ]);
                }

                $warehouseId = $defaultWarehouse->id;
            }

            // Localizar ou vincular o Item correspondente pelo nome/código
            $item = Item::where('name', $material->name)->first();
            if (! $item) {
                $category = ItemCategory::where('code', 'GERAL')->first();
                if (! $category) {
                    $category = ItemCategory::create([
                        'code' => 'GERAL',
                        'name' => 'Insumos Gerais',
                        'type' => 'raw_material',
                    ]);
                }

                $item = Item::create([
                    'category_id' => $category->id,
                    'code' => 'MAT-' . strtoupper(substr(md5($material->name), 0, 8)),
                    'name' => $material->name,
                    'type' => 'raw_material',
                    'unit_of_measure' => $material->unit_of_measure ?: 'UN',
                    'standard_cost' => '0.0000',
                ]);
            }

            // Registrar a baixa no Kardex
            $movement = $this->kardexService->recordMovement([
                'item_id' => $item->id,
                'warehouse_id' => $warehouseId,
                'movement_type' => 'production_consumption',
                'direction' => 'out',
                'quantity' => $quantityToConsume,
                'reference_type' => 'production_orders',
                'reference_id' => $productionOrder->id,
                'user_id' => $userId,
                'notes' => "Consumo OP #{$productionOrder->order_number} - Material: {$material->name}",
            ]);

            // Atualizar status do material na OP
            $newConsumed = bcadd((string) $material->consumed_quantity, $quantityToConsume, 4);
            $material->update([
                'consumed_quantity' => $newConsumed,
                'status' => 'consumed',
            ]);

            return $movement;
        });
    }
}
