<?php

declare(strict_types=1);

namespace App\Modules\CRM\Actions;

use App\Modules\CRM\Enums\DealStatus;
use App\Modules\CRM\Enums\QuoteStatus;
use App\Modules\CRM\Enums\SalesOrderStatus;
use App\Modules\CRM\Models\Quote;
use App\Modules\CRM\Models\SalesOrder;
use App\Modules\CRM\Models\SalesOrderItem;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Production\DTOs\CreateProductionOrderDTO;
use App\Modules\Production\Enums\ProductionOrderPriority;
use App\Modules\Production\Services\ProductionOrderService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ConvertQuoteToSalesOrderAction
{
    public function __construct(
        private readonly ProductionOrderService $productionOrderService,
    ) {}

    public function execute(Quote $quote): SalesOrder
    {
        if ($quote->status === QuoteStatus::Converted) {
            throw new DomainException("O Orçamento {$quote->quote_number} já foi convertido anteriormente.");
        }

        return DB::transaction(function () use ($quote): SalesOrder {
            $quote->load(['items.item', 'items.bom', 'partner', 'deal']);

            $now = Carbon::now();
            $orderNumber = $this->generateOrderNumber();
            $deliveryDate = $now->copy()->addDays($quote->lead_time_days ?: 15);

            // 1. Cria Pedido de Venda
            $salesOrder = SalesOrder::create([
                'order_number' => $orderNumber,
                'partner_id' => $quote->partner_id,
                'quote_id' => $quote->id,
                'deal_id' => $quote->deal_id,
                'status' => SalesOrderStatus::Confirmed,
                'total_amount' => $quote->final_total,
                'delivery_date' => $deliveryDate->toDateString(),
                'payment_terms' => '30 dias',
                'notes' => "Gerado automaticamente a partir do Orçamento {$quote->quote_number} (Conversão 1-Clique).",
            ]);

            // Descobre depósito padrão do Tenant para produção
            $defaultWarehouseId = Warehouse::where('is_active', true)->orderByDesc('is_default')->value('id');

            // 2. Processa cada item do orçamento
            foreach ($quote->items as $quoteItem) {
                $productionOrderId = null;

                // Se for item manufaturado e possuir Ficha Técnica, gera Ordem de Produção automaticamente
                if ($quoteItem->item?->is_manufactured && $quoteItem->bom_id && $defaultWarehouseId) {
                    $itemWarehouseId = $quoteItem->item->default_warehouse_id ?: $defaultWarehouseId;

                    $poDto = new CreateProductionOrderDTO(
                        itemId: $quoteItem->item_id,
                        bomId: $quoteItem->bom_id,
                        warehouseId: $itemWarehouseId,
                        materialsWarehouseId: $defaultWarehouseId,
                        plannedQuantity: (string) $quoteItem->quantity,
                        priority: ProductionOrderPriority::High,
                        startDate: $now->toDateString(),
                        dueDate: $deliveryDate->toDateString(),
                        notes: "Disparada pelo Pedido de Venda {$salesOrder->order_number} (Item: {$quoteItem->item->name})",
                    );

                    $po = $this->productionOrderService->create($poDto);
                    $po = $this->productionOrderService->release($po);
                    $productionOrderId = $po->id;
                }

                // Cria item do pedido de venda
                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'item_id' => $quoteItem->item_id,
                    'bom_id' => $quoteItem->bom_id,
                    'production_order_id' => $productionOrderId,
                    'quantity' => $quoteItem->quantity,
                    'unit_price' => $quoteItem->unit_price,
                    'total_price' => $quoteItem->total_price,
                ]);
            }

            // 3. Atualiza o Orçamento para Convertido
            $quote->update([
                'status' => QuoteStatus::Converted,
                'approved_at' => $quote->approved_at ?: $now,
                'converted_at' => $now,
            ]);

            // 4. Se houver Oportunidade (Deal) vinculada, marca como Ganha (WON)
            if ($quote->deal) {
                $quote->deal->update([
                    'status' => DealStatus::Won,
                    'notes' => trim(($quote->deal->notes ?? '')."\nFechada com sucesso via Pedido de Venda {$salesOrder->order_number}."),
                ]);
            }

            return $salesOrder->load(['items.item', 'items.bom', 'items.productionOrder', 'partner', 'quote', 'deal']);
        });
    }

    private function generateOrderNumber(): string
    {
        $yearMonth = Carbon::now()->format('Ym');
        $count = SalesOrder::where('order_number', 'like', "PV-{$yearMonth}-%")->count() + 1;

        return sprintf('PV-%s-%04d', $yearMonth, $count);
    }
}
