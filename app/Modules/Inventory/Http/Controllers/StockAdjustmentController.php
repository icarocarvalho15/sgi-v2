<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\DTOs\StockMovementDTO;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Http\Requests\StockAdjustmentRequest;
use App\Modules\Inventory\Services\KardexLedgerService;
use Illuminate\Http\RedirectResponse;

final class StockAdjustmentController extends Controller
{
    public function __construct(
        private readonly KardexLedgerService $kardexLedgerService
    ) {}

    public function store(StockAdjustmentRequest $request): RedirectResponse
    {
        $dto = $request->toDTO();

        $movementDTO = new StockMovementDTO(
            warehouseId: $dto->warehouseId,
            itemId: $dto->itemId,
            movementType: $dto->toMovementType(),
            quantity: $dto->quantity,
            unitCost: $dto->unitCost,
            referenceType: 'INVENTORY_ADJUSTMENT',
            justification: $dto->justification,
            userId: $dto->userId
        );

        try {
            $movement = $this->kardexLedgerService->record($movementDTO);

            return back()->with('success', "Ajuste de estoque lançado com sucesso! Movimento: {$movement->movement_number}");
        } catch (InsufficientStockException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }
    }
}
