<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Jobs;

use App\Core\Tenancy\Queue\InteractsWithTenantQueue;
use App\Core\Tenancy\Queue\TenantAwareJob;
use App\Modules\Fiscal\Enums\FiscalInvoiceStatus;
use App\Modules\Fiscal\Models\FiscalInvoice;
use App\Modules\Fiscal\Services\FiscalEmitterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class EmitFiscalInvoiceJob implements ShouldQueue, TenantAwareJob
{
    use Dispatchable, InteractsWithQueue, InteractsWithTenantQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $invoiceId,
        ?string $tenantId = null,
    ) {
        $this->bindTenantContext($tenantId);
    }

    public function handle(FiscalEmitterService $emitter): void
    {
        /** @var FiscalInvoice $invoice */
        $invoice = FiscalInvoice::where('id', $this->invoiceId)->firstOrFail();

        $invoice->update([
            'status' => FiscalInvoiceStatus::Processing,
        ]);

        $emitter->emitNfe($invoice);
    }

    public function failed(Throwable $exception): void
    {
        FiscalInvoice::where('id', $this->invoiceId)->update([
            'status' => FiscalInvoiceStatus::Rejected,
            'rejection_reason' => $exception->getMessage(),
        ]);
    }
}
