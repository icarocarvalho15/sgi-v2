<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\CashFlowReportService;
use App\Modules\Finance\Services\DreReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FinancialReportController extends Controller
{
    public function __construct(
        private readonly CashFlowReportService $cashFlowService,
        private readonly DreReportService $dreService,
    ) {}

    public function cashFlow(Request $request): Response
    {
        $startDate = $request->string('start_date')->toString() ?: null;
        $endDate = $request->string('end_date')->toString() ?: null;

        $report = $this->cashFlowService->generate($startDate, $endDate);

        return Inertia::render('Finance/Reports/CashFlow', [
            'report' => $report,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function dre(Request $request): Response
    {
        $regime = $request->string('regime', 'competence')->toString();
        $startDate = $request->string('start_date')->toString() ?: null;
        $endDate = $request->string('end_date')->toString() ?: null;

        $report = $this->dreService->generate($regime, $startDate, $endDate);

        return Inertia::render('Finance/Reports/Dre', [
            'report' => $report,
            'filters' => [
                'regime' => $regime,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
