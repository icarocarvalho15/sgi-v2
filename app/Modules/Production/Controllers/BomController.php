<?php

declare(strict_types=1);

namespace App\Modules\Production\Controllers;

use App\Core\Security\Rules\TenantExists;
use App\Core\Security\Rules\TenantUnique;
use App\Http\Controllers\Controller;
use App\Modules\Production\Models\Bom;
use App\Modules\Production\Models\WorkCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BomController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $boms = Bom::with(['items.workCenter', 'parentBom'])
            ->orderBy('name')
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json($boms);
        }

        return Inertia::render('Tenant/Production/BOM/Index', [
            'boms' => $boms,
        ]);
    }

    public function create(): Response
    {
        $workCenters = WorkCenter::where('is_active', true)->orderBy('name')->get();
        $otherBoms = Bom::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        return Inertia::render('Tenant/Production/BOM/Form', [
            'workCenters' => $workCenters,
            'otherBoms' => $otherBoms,
            'bom' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', new TenantUnique('boms', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'product_type' => ['required', 'string', 'in:commercial_print,custom_gift,packaging,sub_assembly'],
            'version' => ['nullable', 'integer', 'min:1'],
            'parent_bom_id' => ['nullable', 'uuid', new TenantExists('boms', 'id')],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'string', 'in:substrate,ctp_plate,ink,finishing_supply,sub_assembly'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_of_measure' => ['required', 'string', 'max:10'],
            'items.*.scrap_factor_percent' => ['nullable', 'numeric', 'min:0'],
            'items.*.work_center_id' => ['nullable', 'uuid', new TenantExists('work_centers', 'id')],
            'items.*.child_bom_id' => ['nullable', 'uuid', new TenantExists('boms', 'id')],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $bom = DB::transaction(function () use ($validated): Bom {
            $bom = Bom::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'product_type' => $validated['product_type'],
                'version' => $validated['version'] ?? 1,
                'parent_bom_id' => $validated['parent_bom_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $bom->items()->create($itemData);
            }

            return $bom;
        });

        if ($request->wantsJson()) {
            return response()->json($bom->load('items'), 201);
        }

        return redirect()->route('production.boms.index')
            ->with('success', 'Ficha Técnica (BOM) cadastrada com sucesso.');
    }

    public function show(Bom $bom): Response|JsonResponse
    {
        $bom->load(['items.workCenter', 'items.childBom', 'parentBom']);

        if (request()->wantsJson()) {
            return response()->json($bom);
        }

        return Inertia::render('Tenant/Production/BOM/Show', [
            'bom' => $bom,
        ]);
    }

    public function destroy(Bom $bom): RedirectResponse|JsonResponse
    {
        $bom->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Ficha Técnica excluída com sucesso.']);
        }

        return redirect()->route('production.boms.index')
            ->with('success', 'Ficha Técnica excluída com sucesso.');
    }
}
