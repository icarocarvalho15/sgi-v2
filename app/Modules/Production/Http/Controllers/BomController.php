<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\UnitOfMeasure;
use App\Modules\Production\Http\Requests\StoreBomRequest;
use App\Modules\Production\Models\Bom;
use App\Modules\Production\Models\BomItem;
use App\Modules\Production\Models\RoutingStep;
use App\Modules\Production\Models\WorkCenter;
use App\Modules\Production\Services\BomRollupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class BomController extends Controller
{
    public function __construct(
        private readonly BomRollupService $bomRollupService,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $query = Bom::with(['item.unitOfMeasure', 'items.item', 'items.unitOfMeasure', 'routingSteps.workCenter'])
            ->orderBy('code');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($iq) use ($search) {
                        $iq->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
        }

        $boms = $query->paginate(20)->withQueryString();

        $manufacturedItems = Item::where('is_manufactured', true)->orderBy('name')->get();
        $allItems = Item::where('is_active', true)->orderBy('name')->get();
        $units = UnitOfMeasure::where('is_active', true)->orderBy('code')->get();
        $workCenters = WorkCenter::where('is_active', true)->orderBy('code')->get();

        return Inertia::render('Production/Boms/Index', [
            'boms' => $boms,
            'manufacturedItems' => $manufacturedItems,
            'allItems' => $allItems,
            'units' => $units,
            'workCenters' => $workCenters,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(StoreBomRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $bom = DB::transaction(function () use ($data): Bom {
            if ($data['is_default'] ?? true) {
                Bom::where('item_id', $data['item_id'])->update(['is_default' => false]);
            }

            /** @var Bom $bom */
            $bom = Bom::create([
                'item_id' => $data['item_id'],
                'code' => $data['code'],
                'version' => $data['version'] ?? 1,
                'description' => $data['description'] ?? null,
                'batch_size' => $data['batch_size'] ?? '1.0000',
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? true,
            ]);

            foreach ($data['items'] as $itemData) {
                BomItem::create([
                    'bom_id' => $bom->id,
                    'item_id' => $itemData['item_id'],
                    'unit_of_measure_id' => $itemData['unit_of_measure_id'],
                    'quantity' => $itemData['quantity'],
                    'scrap_rate_percentage' => $itemData['scrap_rate_percentage'] ?? '0.00',
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            if (! empty($data['routing_steps'])) {
                foreach ($data['routing_steps'] as $stepData) {
                    RoutingStep::create([
                        'bom_id' => $bom->id,
                        'work_center_id' => $stepData['work_center_id'],
                        'sequence' => $stepData['sequence'],
                        'name' => $stepData['name'],
                        'description' => $stepData['description'] ?? null,
                        'setup_time_minutes' => $stepData['setup_time_minutes'] ?? '0.00',
                        'run_time_minutes_per_unit' => $stepData['run_time_minutes_per_unit'] ?? '0.00',
                    ]);
                }
            }

            // Calcula Rollup e valida ciclos
            return $this->bomRollupService->calculate($bom);
        });

        return redirect()->route('tenant.production.boms.index')
            ->with('success', "Ficha Técnica {$bom->code} criada com rollup de custo calculado!");
    }

    public function calculateRollup(Bom $bom): RedirectResponse
    {
        $this->bomRollupService->calculate($bom);

        return back()->with('success', "Rollup de custo da BOM {$bom->code} recalculado com sucesso!");
    }
}
