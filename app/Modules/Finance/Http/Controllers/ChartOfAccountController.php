<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Http\Requests\StoreChartOfAccountRequest;
use App\Modules\Finance\Models\ChartOfAccount;
use Illuminate\Http\RedirectResponse;

final class ChartOfAccountController extends Controller
{
    public function store(StoreChartOfAccountRequest $request): RedirectResponse
    {
        ChartOfAccount::create($request->validated());

        return redirect()->route('tenant.finance.accounts.index')
            ->with('success', 'Categoria do plano de contas cadastrada com sucesso!');
    }
}
