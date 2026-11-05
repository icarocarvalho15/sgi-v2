<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Fiscal\Jobs;

use App\Core\Tenancy\Jobs\InteractsWithTenantQueue;
use App\Core\Tenancy\Jobs\TenantAwareJob;
use App\Core\Tenancy\Models\Tenant;
use App\Modules\Integrations\Fiscal\FiscalProviderInterface;
use App\Modules\Integrations\Fiscal\MockFiscalProvider;
use App\Modules\Integrations\Models\FiscalInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IssueFiscalInvoiceJob implements ShouldQueue, TenantAwareJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, InteractsWithTenantQueue;

    public function __construct(
        public string $invoiceId,
        ?string $tenantId = null
    ) {
        if ($tenantId) {
            $this->tenantId = $tenantId;
        } else {
            $this->captureTenantContext();
        }
    }

    public function handle(?FiscalProviderInterface $provider = null): void
    {
        $provider ??= app(MockFiscalProvider::class);

        /** @var FiscalInvoice $invoice */
        $invoice = FiscalInvoice::findOrFail($this->invoiceId);
        /** @var Tenant $tenant */
        $tenant = Tenant::findOrFail($this->tenantId);

        $result = $invoice->type === 'nfse'
            ? $provider->emitNFSe($tenant, $invoice)
            : $provider->emitNFe($tenant, $invoice);

        if ($result['success']) {
            $invoice->update([
                'status' => $result['status'],
                'access_key' => $result['access_key'] ?? $invoice->access_key,
                'protocol_number' => $result['protocol_number'] ?? null,
                'xml_path' => $result['xml_path'] ?? null,
                'pdf_danfe_path' => $result['pdf_danfe_path'] ?? null,
                'issued_at' => now(),
            ]);
        } else {
            $invoice->update([
                'status' => 'rejected',
                'rejection_reason' => $result['rejection_reason'] ?? 'Erro desconhecido na SEFAZ',
            ]);
        }
    }
}
