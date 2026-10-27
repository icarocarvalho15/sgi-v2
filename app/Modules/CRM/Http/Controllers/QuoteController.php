<?php

declare(strict_types=1);

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Actions\ConvertQuoteToSalesOrderAction;
use App\Modules\CRM\Enums\QuoteStatus;
use App\Modules\CRM\Http\Requests\StoreQuoteRequest;
use App\Modules\CRM\Models\CrmDeal;
use App\Modules\CRM\Models\Partner;
use App\Modules\CRM\Models\Quote;
use App\Modules\CRM\Models\QuoteItem;
use App\Modules\CRM\Services\QuotePricingService;
use App\Modules\Inventory\Models\Item;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class QuoteController extends Controller
{
    public function __construct(
        private readonly QuotePricingService $pricingService,
        private readonly ConvertQuoteToSalesOrderAction $convertAction,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $query = Quote::with(['partner', 'deal'])
            ->withCount('items')
            ->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                    ->orWhereHas('partner', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('document_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $quotes = $query->paginate(20)->withQueryString();

        $partners = Partner::where('is_active', true)
            ->whereIn('partner_type', ['CUSTOMER', 'BOTH'])
            ->orderBy('name')
            ->select('id', 'name', 'trade_name', 'document_number')
            ->get();

        $deals = CrmDeal::where('status', 'OPEN')
            ->orderBy('title')
            ->select('id', 'title', 'partner_id')
            ->get();

        $items = Item::with(['boms' => function ($bq) {
            $bq->where('is_active', true);
        }])
            ->where('is_active', true)
            ->where(function ($iq) {
                $iq->where('is_sellable', true)
                    ->orWhere('is_manufactured', true);
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('Sales/Quotes/Index', [
            'quotes' => $quotes,
            'partners' => $partners,
            'deals' => $deals,
            'items' => $items,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function show(Quote $quote): Response
    {
        $quote->load(['partner.addresses', 'deal', 'items.item.unitOfMeasure', 'items.bom', 'salesOrder']);

        return Inertia::render('Sales/Quotes/Show', [
            'quote' => $quote,
        ]);
    }

    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $quoteNumber = $this->generateQuoteNumber();

        $quote = Quote::create([
            'quote_number' => $quoteNumber,
            'partner_id' => $data['partner_id'],
            'deal_id' => $data['deal_id'] ?? null,
            'status' => QuoteStatus::Draft,
            'markup_percentage' => $data['markup_percentage'] ?? '30.00',
            'lead_time_days' => $data['lead_time_days'] ?? 15,
            'valid_until' => $data['valid_until'] ?? Carbon::now()->addDays(15)->toDateString(),
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['items'] as $itemData) {
            $pricing = $this->pricingService->calculateItemPricing([
                'item_id' => $itemData['item_id'],
                'bom_id' => $itemData['bom_id'] ?? null,
                'quantity' => $itemData['quantity'],
                'markup_percentage' => $itemData['markup_percentage'] ?? $quote->markup_percentage,
            ]);

            QuoteItem::create([
                'quote_id' => $quote->id,
                'item_id' => $itemData['item_id'],
                'bom_id' => $itemData['bom_id'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit_cost' => $pricing['unit_cost'],
                'markup_percentage' => $pricing['markup_percentage'],
                'unit_price' => $pricing['unit_price'],
                'total_price' => $pricing['total_price'],
                'lead_time_days' => $pricing['lead_time_days'],
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        $quote = $this->pricingService->recalculateQuote($quote);

        return redirect()->route('tenant.sales.quotes.show', $quote->id)
            ->with('success', "Orçamento {$quote->quote_number} criado e calculado com sucesso!");
    }

    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'item_id' => ['required', 'uuid'],
            'bom_id' => ['nullable', 'uuid'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'markup_percentage' => ['nullable', 'numeric', 'min:0'],
        ]);

        $pricing = $this->pricingService->calculateItemPricing([
            'item_id' => $request->string('item_id')->toString(),
            'bom_id' => $request->string('bom_id')->toString() ?: null,
            'quantity' => $request->string('quantity')->toString(),
            'markup_percentage' => $request->string('markup_percentage')->toString() ?: '30.00',
        ]);

        return response()->json($pricing);
    }

    public function convert(Quote $quote): RedirectResponse
    {
        $salesOrder = $this->convertAction->execute($quote);

        return redirect()->route('tenant.sales.orders.show', $salesOrder->id)
            ->with('success', "Conversão 1-Clique realizada com sucesso! Pedido de Venda {$salesOrder->order_number} gerado.");
    }

    private function generateQuoteNumber(): string
    {
        $yearMonth = Carbon::now()->format('Ym');
        $count = Quote::where('quote_number', 'like', "ORC-{$yearMonth}-%")->count() + 1;

        return sprintf('ORC-%s-%04d', $yearMonth, $count);
    }
}
