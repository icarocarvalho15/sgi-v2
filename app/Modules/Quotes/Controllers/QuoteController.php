<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Controllers;

use App\Core\Security\Rules\TenantExists;
use App\Http\Controllers\Controller;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\CRM\Models\Partner;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use App\Modules\Quotes\Services\GraphicCostCalculatorService;
use App\Modules\Quotes\Services\QuoteConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $view = $request->query('view', 'table');

        $statuses = TenantStatus::forModule('quotes')
            ->ordered()
            ->get(['id', 'name', 'slug', 'color', 'position', 'is_default', 'is_final']);

        $quotesQuery = Quote::with(['customer', 'status', 'items'])
            ->orderBy('created_at', 'desc');

        if ($request->wantsJson()) {
            return response()->json([
                'quotes' => $quotesQuery->get(),
                'statuses' => $statuses,
            ]);
        }

        $paginatedQuotes = (clone $quotesQuery)->paginate(15)->withQueryString();
        $allQuotes = $quotesQuery->get();

        return Inertia::render('Quotes/Index', [
            'quotes' => $paginatedQuotes,
            'kanbanQuotes' => $allQuotes,
            'statuses' => $statuses,
            'initialView' => $view,
        ]);
    }

    public function create(): Response
    {
        $customers = Partner::whereIn('type', ['customer', 'both'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'trade_name', 'document_number']);

        $statuses = TenantStatus::forModule('quotes')
            ->ordered()
            ->get(['id', 'name', 'color', 'is_default', 'is_final']);

        return Inertia::render('Quotes/Create', [
            'customers' => $customers,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Endpoint for dynamic simulation of technical printing / gift costs.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'colors_front' => ['required', 'integer', 'min:0', 'max:12'],
            'colors_back' => ['required', 'integer', 'min:0', 'max:12'],
            'product_type' => ['nullable', 'string'],
            'ctp_plate_cost' => ['nullable', 'numeric'],
            'substrate_unit_cost' => ['nullable', 'numeric'],
            'machine_run_cost_per_thousand' => ['nullable', 'numeric'],
            'customization_unit_cost' => ['nullable', 'numeric'],
            'finishing_flat_cost' => ['nullable', 'numeric'],
            'markup_multiplier' => ['nullable', 'numeric'],
        ]);

        $calculation = GraphicCostCalculatorService::calculate($validated);

        return response()->json($calculation);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'uuid', new TenantExists('partners', 'id')],
            'status_id' => ['required', 'uuid', new TenantExists('tenant_statuses', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'production_lead_time_days' => ['required', 'integer', 'min:1'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_type' => ['required', 'string', 'in:commercial_print,custom_gift'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.colors_front' => ['required', 'integer', 'min:0'],
            'items.*.colors_back' => ['required', 'integer', 'min:0'],
            'items.*.format_open' => ['nullable', 'string', 'max:50'],
            'items.*.format_closed' => ['nullable', 'string', 'max:50'],
            'items.*.substrate_type' => ['nullable', 'string', 'max:100'],
            'items.*.customization_process' => ['nullable', 'string', 'max:100'],
            'items.*.finishing_operations' => ['nullable', 'array'],
            'items.*.ctp_plate_cost' => ['nullable', 'numeric'],
            'items.*.substrate_unit_cost' => ['nullable', 'numeric'],
            'items.*.machine_run_cost_per_thousand' => ['nullable', 'numeric'],
            'items.*.customization_unit_cost' => ['nullable', 'numeric'],
            'items.*.finishing_flat_cost' => ['nullable', 'numeric'],
            'items.*.markup_multiplier' => ['nullable', 'numeric'],
        ]);

        $quote = DB::transaction(function () use ($validated): Quote {
            $quoteNumber = 'ORC-' . date('Y') . '-' . strtoupper(Str::random(6));

            $totalEstimatedCost = '0.0000';
            $subtotal = '0.0000';

            $itemsToCreate = [];

            foreach ($validated['items'] as $itemData) {
                $calc = GraphicCostCalculatorService::calculate($itemData);

                $totalEstimatedCost = bcadd($totalEstimatedCost, $calc['total_estimated_cost'], 4);
                $subtotal = bcadd($subtotal, $calc['total_price'], 4);

                $itemsToCreate[] = [
                    'product_type' => $itemData['product_type'],
                    'name' => $itemData['name'],
                    'quantity' => $itemData['quantity'],
                    'format_open' => $itemData['format_open'] ?? null,
                    'format_closed' => $itemData['format_closed'] ?? null,
                    'substrate_type' => $itemData['substrate_type'] ?? null,
                    'colors_front' => $itemData['colors_front'],
                    'colors_back' => $itemData['colors_back'],
                    'ctp_plates_count' => $calc['ctp_plates_count'],
                    'customization_process' => $itemData['customization_process'] ?? null,
                    'finishing_operations' => $itemData['finishing_operations'] ?? [],
                    'unit_cost' => $calc['unit_cost'],
                    'markup_multiplier' => $calc['markup_multiplier'],
                    'unit_price' => $calc['unit_price'],
                    'total_price' => $calc['total_price'],
                ];
            }

            $quote = Quote::create([
                'quote_number' => $quoteNumber,
                'customer_id' => $validated['customer_id'],
                'status_id' => $validated['status_id'],
                'title' => $validated['title'],
                'production_lead_time_days' => $validated['production_lead_time_days'],
                'valid_until' => $validated['valid_until'] ?? now()->addDays(15),
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'estimated_cost' => $totalEstimatedCost,
            ]);

            foreach ($itemsToCreate as $item) {
                $quote->items()->create($item);
            }

            return $quote;
        });

        if ($request->wantsJson()) {
            return response()->json($quote->load(['items', 'customer', 'status']), 201);
        }

        return redirect()->route('quotes.show', $quote->id)
            ->with('success', 'Orçamento técnico gerado com sucesso.');
    }

    public function show(Quote $quote): Response|JsonResponse
    {
        $quote->load(['customer.addresses', 'status', 'items', 'salesOrders']);

        if (request()->wantsJson()) {
            return response()->json($quote);
        }

        return Inertia::render('Quotes/Show', [
            'quote' => $quote,
        ]);
    }

    /**
     * 1-Click Quote Conversion endpoint: generates Sales Order and shop-floor Production Orders.
     */
    public function convert(Quote $quote): RedirectResponse|JsonResponse
    {
        $result = QuoteConversionService::convert($quote);

        if (request()->wantsJson()) {
            return response()->json([
                'message' => 'Orçamento convertido com sucesso em Pedido de Venda e Ordens de Produção.',
                'sales_order' => $result['sales_order']->load(['items', 'customer']),
                'production_orders' => $result['production_orders'],
            ]);
        }

        return redirect()->route('quotes.show', $quote->id)
            ->with('success', 'Orçamento convertido com sucesso em Pedido de Venda e Ordens de Produção.');
    }

    /**
     * Updates the operational status of a quote (used by Funil / Kanban).
     */
    public function updateStatus(Request $request, Quote $quote): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'status_id' => ['required', 'uuid', new TenantExists('tenant_statuses', 'id')],
        ]);

        $status = TenantStatus::where('id', $validated['status_id'])
            ->where('module', 'quotes')
            ->firstOrFail();

        $quote->update([
            'status_id' => $status->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Status do orçamento {$quote->quote_number} atualizado para {$status->name}.",
                'quote' => $quote->load(['customer', 'status', 'items']),
            ]);
        }

        return back()->with('success', "Status do orçamento {$quote->quote_number} atualizado para {$status->name}.");
    }
}
