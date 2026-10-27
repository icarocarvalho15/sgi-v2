<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Http\Requests\StoreFinancialAccountRequest;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\CostCenter;
use App\Modules\Finance\Models\FinancialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FinancialAccountController extends Controller
{
    public function index(Request $request): Response
    {
        $accounts = FinancialAccount::orderBy('name')->get();
        $chartOfAccounts = ChartOfAccount::orderBy('code')->get();
        $costCenters = CostCenter::orderBy('code')->get();

        return Inertia::render('Finance/Accounts/Index', [
            'accounts' => $accounts,
            'chartOfAccounts' => $chartOfAccounts,
            'costCenters' => $costCenters,
        ]);
    }

    public function store(StoreFinancialAccountRequest $request): RedirectResponse
    {
        FinancialAccount::create($request->validated());

        return redirect()->route('tenant.finance.accounts.index')
            ->with('success', 'Conta financeira / Caixa cadastrado com sucesso!');
    }
}
