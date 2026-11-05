<?php

declare(strict_types=1);

namespace App\Modules\Finance\Controllers;

use App\Core\Security\Rules\TenantExists;
use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Partner;
use App\Modules\Finance\Models\BankAccount;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\CostCenter;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Services\FinancialEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialEntryController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $type = $request->input('type'); // receivable, payable
        $status = $request->input('status'); // pending, partial, paid, cancelled
        $search = $request->input('search');

        $query = FinancialEntry::with(['partner', 'chartOfAccount', 'costCenter', 'bankAccount'])
            ->orderBy('due_date', 'asc');

        if ($type) {
            $query->where('type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'ilike', "%{$search}%")
                    ->orWhere('entry_number', 'ilike', "%{$search}%");
            });
        }

        $entries = $query->paginate(15)->withQueryString();

        $totalReceivable = FinancialEntry::where('type', 'receivable')->whereIn('status', ['pending', 'partial'])->sum('balance_due');
        $totalPayable = FinancialEntry::where('type', 'payable')->whereIn('status', ['pending', 'partial'])->sum('balance_due');
        $bankAccounts = BankAccount::where('is_active', true)->get();

        if ($request->wantsJson()) {
            return response()->json($entries);
        }

        return Inertia::render('Tenant/Finance/Entries/Index', [
            'entries' => $entries,
            'bankAccounts' => $bankAccounts,
            'filters' => [
                'type' => $type,
                'status' => $status,
                'search' => $search,
            ],
            'metrics' => [
                'total_receivable' => $totalReceivable,
                'total_payable' => $totalPayable,
            ],
        ]);
    }

    public function create(): Response
    {
        $partners = Partner::where('is_active', true)->orderBy('name')->get(['id', 'name', 'type']);
        $chartOfAccounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'type']);
        $costCenters = CostCenter::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get(['id', 'name', 'current_balance']);

        return Inertia::render('Tenant/Finance/Entries/Create', [
            'partners' => $partners,
            'chartOfAccounts' => $chartOfAccounts,
            'costCenters' => $costCenters,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function store(Request $request, FinancialEntryService $service): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:receivable,payable'],
            'partner_id' => ['nullable', 'uuid', new TenantExists('partners')],
            'chart_of_account_id' => ['nullable', 'uuid', new TenantExists('chart_of_accounts')],
            'cost_center_id' => ['nullable', 'uuid', new TenantExists('cost_centers')],
            'bank_account_id' => ['nullable', 'uuid', new TenantExists('bank_accounts')],
            'description' => ['required', 'string', 'max:255'],
            'original_amount' => ['required', 'numeric', 'gt:0'],
            'issue_date' => ['nullable', 'date'],
            'first_due_date' => ['required', 'date'],
            'competence_date' => ['nullable', 'date'],
            'total_installments' => ['nullable', 'integer', 'min:1', 'max:48'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->createEntriesWithInstallments($validated);

        return redirect()->route('tenant.finance.entries.index')
            ->with('success', 'Lançamento(s) financeiro(s) registrado(s) com sucesso!');
    }
}
