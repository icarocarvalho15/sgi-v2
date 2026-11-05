<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Production\Models\ProductionOrderMaterial;
use App\Modules\Production\Models\ProductionTimeLog;
use Illuminate\Support\Facades\DB;

final class ProductionOrderService
{
    /**
     * Advances the Production Order to a new stage/status defined in tenant_statuses.
     */
    public static function updateStatus(ProductionOrder $order, string $newStatusId): ProductionOrder
    {
        $status = TenantStatus::forModule('production_orders')
            ->where('id', $newStatusId)
            ->firstOrFail();

        $order->update(['status_id' => $status->id]);

        return $order->refresh();
    }

    /**
     * Allocates shop-floor materials based on the product BOM and OP target quantity.
     * Computes scrap factor for paper, CTP plates and inks.
     */
    public static function allocateMaterialsFromBom(ProductionOrder $order): array
    {
        if (empty($order->bom_id)) {
            return [];
        }

        return DB::transaction(function () use ($order): array {
            $bom = $order->bom()->with('items')->firstOrFail();
            $materials = [];

            foreach ($bom->items as $item) {
                // planned = (item_qty * target_qty) * (1 + scrap_percent / 100)
                $baseQty = bcmul((string) $item->quantity, (string) $order->target_quantity, 4);
                $scrapFactor = bcadd('1.0000', bcdiv((string) $item->scrap_factor_percent, '100', 4), 4);
                $plannedQty = bcmul($baseQty, $scrapFactor, 4);

                $materials[] = ProductionOrderMaterial::create([
                    'production_order_id' => $order->id,
                    'bom_item_id' => $item->id,
                    'name' => $item->name,
                    'item_type' => $item->item_type,
                    'planned_quantity' => $plannedQty,
                    'consumed_quantity' => '0.0000',
                    'unit_of_measure' => $item->unit_of_measure,
                    'status' => 'reserved',
                ]);
            }

            return $materials;
        });
    }

    /**
     * Records a shop floor time & production log and increments produced quantity.
     */
    public static function logOperation(ProductionOrder $order, array $data): ProductionTimeLog
    {
        return DB::transaction(function () use ($order, $data): ProductionTimeLog {
            $log = ProductionTimeLog::create([
                'production_order_id' => $order->id,
                'work_center_id' => $data['work_center_id'],
                'user_id' => $data['user_id'] ?? null,
                'operation_name' => $data['operation_name'],
                'started_at' => $data['started_at'],
                'ended_at' => $data['ended_at'] ?? now(),
                'good_quantity' => $data['good_quantity'] ?? 0,
                'scrap_quantity' => $data['scrap_quantity'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            // Update produced quantity on the production order
            $order->increment('produced_quantity', (int) ($data['good_quantity'] ?? 0));

            return $log;
        });
    }
}
