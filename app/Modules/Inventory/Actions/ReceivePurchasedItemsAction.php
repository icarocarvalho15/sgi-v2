<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\Models\KardexMovement;
use App\Modules\Inventory\Services\KardexLedgerService;

class ReceivePurchasedItemsAction
{
    public function __construct(
        protected KardexLedgerService $kardexService
    ) {}

    /**
     * Registra o recebimento de compra de insumos/produtos e atualiza o CMP.
     */
    public function execute(
        string $itemId,
        string $warehouseId,
        string $quantity,
        string $unitCost,
        ?string $purchaseOrderId = null,
        ?string $userId = null,
        ?string $notes = null
    ): KardexMovement {
        return $this->kardexService->recordMovement([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'movement_type' => 'purchase_receipt',
            'direction' => 'in',
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'reference_type' => $purchaseOrderId ? 'purchase_orders' : null,
            'reference_id' => $purchaseOrderId,
            'user_id' => $userId,
            'notes' => $notes ?? 'Recebimento de compra de insumos',
        ]);
    }
}
