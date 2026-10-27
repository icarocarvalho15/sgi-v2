<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Http\Requests\StoreCostCenterRequest;
use App\Modules\Finance\Models\CostCenter;
use Illuminate\Http\RedirectResponse;

final class CostCenterController extends Controller
{
    public function store(StoreCostCenterRequest $request): RedirectResponse
    {
        CostCenter::create($request->validated());

        return redirect()->route('tenant.finance.accounts.index')
            ->with('success', 'Centro de custo cadastrado com sucesso!');
    }
}
