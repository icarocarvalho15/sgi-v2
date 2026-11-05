<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Services;

use App\Core\Tenancy\TenantContext;
use App\Modules\CoreAdmin\Models\TenantStatus;
use App\Modules\Production\Models\ProductionOrder;
use App\Modules\Quotes\Models\Quote;
use App\Modules\SalesOrders\Models\SalesOrder;
use App\Modules\SalesOrders\Models\SalesOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class QuoteConversionService
{
    /**
     * Converts an approved quote into a Sales Order and shop-floor Production Orders in 1-Click.
     * Guaranteed atomic with DB::transaction and pessimistic lock.
     *
     * @return array{
     *     sales_order: SalesOrder,
     *     production_orders: array<int, ProductionOrder>
     * }
     */
    public static function convert(Quote $quote, ?string $orderNumber = null): array
    {
        return DB::transaction(function () use ($quote, $orderNumber): array {
            // Pessimistic lock on the quote
            $lockedQuote = Quote::where('id', $quote->id)->lockForUpdate()->firstOrFail();

            $tenantId = TenantContext::id();

            // 1. Identify 'orcamento-aprovado' status or first final status
            $approvedStatus = TenantStatus::forModule('quotes')
                ->where('slug', 'orcamento-aprovado')
                ->first()
                ?? TenantStatus::forModule('quotes')->where('is_final', true)->first()
                ?? $lockedQuote->status;

            // 2. Identify initial production order status (e.g. 'pre-impressao' or first default)
            $initialProductionStatus = TenantStatus::forModule('production_orders')
                ->where('slug', 'pre-impressao')
                ->first()
                ?? TenantStatus::forModule('production_orders')->where('is_default', true)->first()
                ?? TenantStatus::forModule('production_orders')->firstOrFail();

            // 3. Identify sales order status
            $salesOrderStatus = TenantStatus::forModule('sales_kanban')
                ->where('slug', 'venda-fechada')
                ->first()
                ?? TenantStatus::forModule('quotes')->first();

            $generatedOrderNumber = $orderNumber ?? 'PV-' . date('Y') . '-' . strtoupper(Str::random(6));

            // 4. Create Sales Order
            $salesOrder = SalesOrder::create([
                'order_number' => $generatedOrderNumber,
                'quote_id' => $lockedQuote->id,
                'customer_id' => $lockedQuote->customer_id,
                'status_id' => $salesOrderStatus?->id ?? $lockedQuote->status_id,
                'total_amount' => $lockedQuote->total_amount,
                'delivery_date' => now()->addDays($lockedQuote->production_lead_time_days),
                'notes' => 'Gerado automaticamente via Conversão em 1-Clique do Orçamento ' . $lockedQuote->quote_number,
            ]);

            $productionOrders = [];
            $quoteItems = $lockedQuote->items()->get();

            // 5. For each item in quote, generate SalesOrderItem and ProductionOrder
            foreach ($quoteItems as $index => $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'quote_item_id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ]);

                $opNumber = 'OP-' . date('Y') . '-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4));

                $productionOrders[] = ProductionOrder::create([
                    'order_number' => $opNumber,
                    'sales_order_id' => $salesOrder->id,
                    'quote_item_id' => $item->id,
                    'status_id' => $initialProductionStatus->id,
                    'product_name' => $item->name,
                    'target_quantity' => $item->quantity,
                    'produced_quantity' => 0,
                    'start_date' => now(),
                    'due_date' => now()->addDays($lockedQuote->production_lead_time_days),
                    'technical_notes' => [
                        'format_open' => $item->format_open,
                        'format_closed' => $item->format_closed,
                        'substrate_type' => $item->substrate_type,
                        'colors_front' => $item->colors_front,
                        'colors_back' => $item->colors_back,
                        'ctp_plates_count' => $item->ctp_plates_count,
                        'customization_process' => $item->customization_process,
                        'finishing_operations' => $item->finishing_operations,
                    ],
                ]);
            }

            // 6. Update quote status to approved and record conversion metadata
            $metadata = $lockedQuote->metadata ?? [];
            $metadata['converted_to_order_id'] = $salesOrder->id;
            $metadata['converted_at'] = now()->toIso8601String();

            $lockedQuote->update([
                'status_id' => $approvedStatus->id,
                'metadata' => $metadata,
            ]);

            return [
                'sales_order' => $salesOrder,
                'production_orders' => $productionOrders,
            ];
        });
    }
}
