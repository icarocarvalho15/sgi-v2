<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Controllers;

use App\Core\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Partner;
use App\Modules\CRM\Models\SalesOrder;
use App\Modules\Fiscal\Enums\FiscalInvoiceStatus;
use App\Modules\Fiscal\Http\Requests\EmitFiscalInvoiceRequest;
use App\Modules\Fiscal\Jobs\EmitFiscalInvoiceJob;
use App\Modules\Fiscal\Models\FiscalInvoice;
use App\Modules\Fiscal\Models\TenantTaxSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FiscalInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $type = $request->string('type')->toString();

        $query = FiscalInvoice::with(['partner', 'salesOrder'])
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('access_key', 'like', "%{$search}%")
                    ->orWhere('protocol_number', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('partner', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($type !== '') {
            $query->where('invoice_type', $type);
        }

        $invoices = $query->paginate(20)->withQueryString();

        $partners = Partner::where('is_active', true)->orderBy('name')->get(['id', 'name', 'document_number']);
        $salesOrders = SalesOrder::orderByDesc('created_at')->limit(50)->get(['id', 'order_number', 'total_amount']);

        return Inertia::render('Fiscal/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'type' => $type,
            ],
            'partners' => $partners,
            'salesOrders' => $salesOrders,
        ]);
    }

    public function show(FiscalInvoice $invoice): Response
    {
        $invoice->load(['partner', 'salesOrder']);

        return Inertia::render('Fiscal/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function store(EmitFiscalInvoiceRequest $request): RedirectResponse
    {
        $tenantId = app(TenantContext::class)->id();
        $data = $request->validated();

        /** @var TenantTaxSetting $taxSetting */
        $taxSetting = TenantTaxSetting::firstOrCreate(['tenant_id' => $tenantId], [
            'nfe_series' => 1,
            'next_nfe_number' => 1,
            'environment' => 'HOMOLOGATION',
        ]);

        $nextNumber = (int) $taxSetting->next_nfe_number;
        $series = (int) $taxSetting->nfe_series;
        $env = (string) $taxSetting->environment->value;

        /** @var FiscalInvoice $invoice */
        $invoice = FiscalInvoice::create([
            'partner_id' => $data['partner_id'],
            'sales_order_id' => $data['sales_order_id'] ?? null,
            'invoice_type' => $data['invoice_type'],
            'invoice_number' => $nextNumber,
            'series' => $series,
            'environment' => $env,
            'status' => FiscalInvoiceStatus::Draft,
            'total_amount' => (string) $data['total_amount'],
        ]);

        // Dispara o Job assíncrono multi-tenant na fila
        EmitFiscalInvoiceJob::dispatch($invoice->id, $invoice->tenant_id);

        return redirect()->route('tenant.fiscal.invoices.index')
            ->with('success', "Nota Fiscal nº {$nextNumber} gerada e enviada para a fila de transmissão SEFAZ!");
    }

    public function downloadXml(FiscalInvoice $invoice): StreamedResponse
    {
        if (empty($invoice->xml_path) || ! Storage::disk('local')->exists($invoice->xml_path)) {
            abort(404, 'Arquivo XML não localizado no storage privado.');
        }

        return Storage::disk('local')->download($invoice->xml_path, "NFe_{$invoice->access_key}.xml");
    }

    public function downloadPdf(FiscalInvoice $invoice): StreamedResponse
    {
        if (empty($invoice->pdf_path) || ! Storage::disk('local')->exists($invoice->pdf_path)) {
            abort(404, 'Arquivo DANFE não localizado no storage privado.');
        }

        return Storage::disk('local')->download($invoice->pdf_path, "DANFE_{$invoice->access_key}.pdf");
    }
}
