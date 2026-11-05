<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Core\Tenancy\TenantContext;
use App\Modules\Production\Exceptions\BomCircularReferenceException;
use App\Modules\Production\Models\Bom;
use App\Modules\Production\Models\BomItem;
use Illuminate\Support\Facades\DB;

final class BomValidationService
{
    /**
     * Asserts that adding $childBomId into $parentBomId will not create a circular dependency.
     * Throws BomCircularReferenceException if a cycle is detected.
     */
    public static function assertNoCycle(string $parentBomId, string $childBomId): void
    {
        if ($parentBomId === $childBomId) {
            throw new BomCircularReferenceException(
                "A Ficha Técnica [{$parentBomId}] não pode conter a si mesma como insumo ou subconjunto."
            );
        }

        $visited = [];
        $queue = [$childBomId];

        while (! empty($queue)) {
            $currentId = array_shift($queue);

            if ($currentId === $parentBomId) {
                throw new BomCircularReferenceException(
                    "Dependência circular detectada: a Ficha Técnica [{$childBomId}] já possui [{$parentBomId}] em sua cadeia de subconjuntos."
                );
            }

            if (isset($visited[$currentId])) {
                continue;
            }
            $visited[$currentId] = true;

            // Find all child BOMs referenced inside current BOM's items
            $subBomIds = BomItem::query()
                ->where('bom_id', $currentId)
                ->whereNotNull('child_bom_id')
                ->pluck('child_bom_id')
                ->all();

            foreach ($subBomIds as $subId) {
                if ($subId === $parentBomId) {
                    throw new BomCircularReferenceException(
                        "Dependência circular detectada: a Ficha Técnica filha [{$subId}] referencia direta ou indiretamente a Ficha Técnica pai [{$parentBomId}]."
                    );
                }
                if (! isset($visited[$subId])) {
                    $queue[] = $subId;
                }
            }
        }
    }
}
