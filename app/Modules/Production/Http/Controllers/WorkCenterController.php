<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Production\Http\Requests\StoreWorkCenterRequest;
use App\Modules\Production\Models\WorkCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WorkCenterController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $query = WorkCenter::orderBy('code');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $workCenters = $query->paginate(20)->withQueryString();

        return Inertia::render('Production/WorkCenters/Index', [
            'workCenters' => $workCenters,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(StoreWorkCenterRequest $request): RedirectResponse
    {
        WorkCenter::create($request->validated());

        return redirect()->route('tenant.production.work-centers.index')
            ->with('success', 'Centro de trabalho cadastrado com sucesso!');
    }

    public function update(StoreWorkCenterRequest $request, WorkCenter $workCenter): RedirectResponse
    {
        $workCenter->update($request->validated());

        return redirect()->route('tenant.production.work-centers.index')
            ->with('success', 'Centro de trabalho atualizado com sucesso!');
    }
}
