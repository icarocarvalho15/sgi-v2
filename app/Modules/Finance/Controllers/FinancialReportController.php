<?php

declare(strict_types=1);

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\CashFlowReportService;
use App\Modules\Finance\Services\DreReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialReportController extends Controller
{
    public function index(
        Request $request,
        CashFlowReportService $cashFlowService,
        DreReportService $dreService
    ): Response|JsonResponse {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $cashFlow = $cashFlowService->generate($startDate, $endDate);
        $dre = $dreService->generate($startDate, $endDate);

        if ($request->wantsJson()) {
            return response()->json([
                'cash_flow' => $cashFlow,
                'dre' => $dre,
            ]);
        }

        return Inertia::render('Tenant/Finance/Reports/CashFlow', [
            'cashFlow' => $cashFlow,
            'dre' => $dre,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
