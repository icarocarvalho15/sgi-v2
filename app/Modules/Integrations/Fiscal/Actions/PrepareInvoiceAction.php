<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Fiscal\Actions;

use App\Modules\CRM\Models\Partner;
use App\Modules\Integrations\Fiscal\Jobs\IssueFiscalInvoiceJob;
use App\Modules\Integrations\Models\FiscalInvoice;
use App\Modules\Integrations\Models\TenantFiscalSetting;
use App\Modules\SalesOrders\Models\SalesOrder;
use InvalidArgumentException;

class PrepareInvoiceAction
{
    /**
     * Valida os campos fiscais obrigatórios, cria a FiscalInvoice em status 'processing'
     * e despacha o job assíncrono TenantAwareJob.
     */
    public function execute(
        SalesOrder $order,
        Partner $customer,
        string $type = 'nfe'
    ): FiscalInvoice {
        // Validação Fiscal: Documento do cliente
        if (empty($customer->document_number)) {
            throw new InvalidArgumentException('O cliente deve possuir CPF ou CNPJ válido para emissão de nota fiscal.');
        }

        // Validação das configurações fiscais do Tenant
        /** @var TenantFiscalSetting|null $settings */
        $settings = TenantFiscalSetting::first();
        if (! $settings || empty($settings->cnpj)) {
            throw new InvalidArgumentException('As configurações fiscais do Tenant (CNPJ e Certificado) não foram preenchidas.');
        }

        $nextNumber = $type === 'nfse' ? $settings->next_nfse_number : $settings->next_nfe_number;
        $series = $type === 'nfse' ? $settings->nfse_series : $settings->nfe_series;

        // Monta os itens fiscais a partir dos itens do pedido
        $itemsData = [];
        foreach ($order->items as $item) {
            $itemsData[] = [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'ncm' => $settings->default_ncm ?: '49111090', // Impressos publicitários
                'cfop' => $settings->default_cfop ?: '5101',
            ];
        }

        // Cria a nota fiscal em rascunho/processamento
        $invoice = FiscalInvoice::create([
            'type' => $type,
            'sales_order_id' => $order->id,
            'partner_id' => $customer->id,
            'status' => 'processing',
            'invoice_number' => $nextNumber,
            'series' => $series,
            'total_amount' => $order->total_amount,
            'items_data' => $itemsData,
        ]);

        // Incrementa o número da próxima nota
        if ($type === 'nfse') {
            $settings->increment('next_nfse_number');
        } else {
            $settings->increment('next_nfe_number');
        }

        // Despacha o Job assíncrono com propagação de contexto de Tenant
        IssueFiscalInvoiceJob::dispatch($invoice->id);

        return $invoice;
    }
}
