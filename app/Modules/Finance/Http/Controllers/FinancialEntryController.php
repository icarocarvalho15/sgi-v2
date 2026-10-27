<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Partner;
use App\Modules\Finance\DTOs\CreateFinancialEntryDTO;
use App\Modules\Finance\Enums\EntryType;
use App\Modules\Finance\Http\Requests\StoreFinancialEntryRequest;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\CostCenter;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Finance\Services\FinancialEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class FinancialEntryController extends Controller
{
    public function __construct(
        private readonly FinancialEntryService $entryService,
    ) {}

    public function index(Request $request): Response
    {
        $entryTypeStr = $request->string('entry_type', EntryType::Receivable->value)->toString();
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        $query = FinancialEntry::with(['partner', 'chartOfAccount', 'costCenter'])
            ->orderBy('due_date', 'asc');

        if ($entryTypeStr !== '') {
            $query->where('entry_type', $entryTypeStr);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($startDate !== '') {
            $query->whereDate('due_date', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('due_date', '<=', $endDate);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhereHas('partner', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $entries = $query->paginate(20)->withQueryString();

        $accounts = FinancialAccount::where('is_active', true)->orderBy('name')->get();
        $chartOfAccounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        $costCenters = CostCenter::where('is_active', true)->orderBy('code')->get();
        $partners = Partner::where('is_active', true)->orderBy('name')->get(['id', 'name', 'partner_type']);

        return Inertia::render('Finance/Entries/Index', [
            'entries' => $entries,
            'filters' => [
                'entry_type' => $entryTypeStr,
                'status' => $status,
                'search' => $search,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'accounts' => $accounts,
            'chartOfAccounts' => $chartOfAccounts,
            'costCenters' => $costCenters,
            'partners' => $partners,
        ]);
    }

    public function store(StoreFinancialEntryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $dto = new CreateFinancialEntryDTO(
            entryType: EntryType::from($data['entry_type']),
            chartOfAccountId: $data['chart_of_account_id'],
            documentNumber: $data['document_number'] ?? ('DOC-'.strtoupper(Str::random(6))),
            title: $data['title'],
            totalAmount: (string) $data['total_amount'],
            firstDueDate: $data['first_due_date'],
            partnerId: $data['partner_id'] ?? null,
            costCenterId: $data['cost_center_id'] ?? null,
            salesOrderId: $data['sales_order_id'] ?? null,
            installmentsCount: (int) ($data['installments_count'] ?? 1),
            issueDate: $data['issue_date'] ?? null,
            competenceDate: $data['competence_date'] ?? null,
            intervalDays: (int) ($data['interval_days'] ?? 30),
            notes: $data['notes'] ?? null,
        );

        $created = $this->entryService->createWithInstallments($dto);
        $count = count($created);

        $typeLabel = $dto->entryType === EntryType::Receivable ? 'a receber' : 'a pagar';

        return redirect()->route('tenant.finance.entries.index', ['entry_type' => $dto->entryType->value])
            ->with('success', "Título {$typeLabel} gerado com sucesso ({$count} parcela(s))!");
    }

    public function show(FinancialEntry $entry): Response
    {
        $entry->load([
            'partner',
            'chartOfAccount',
            'costCenter',
            'salesOrder',
            'payments.account',
        ]);

        $accounts = FinancialAccount::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Finance/Entries/Show', [
            'entry' => $entry,
            'accounts' => $accounts,
        ]);
    }
}
