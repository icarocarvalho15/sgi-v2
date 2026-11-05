<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Fiscal;

use App\Core\Tenancy\Models\Tenant;
use App\Modules\Integrations\Models\FiscalInvoice;

interface FiscalProviderInterface
{
    /**
     * Transmite a NFe ao provedor fiscal / SEFAZ.
     *
     * @return array{
     *     success: bool,
     *     status: 'authorized'|'rejected'|'processing',
     *     access_key?: string,
     *     protocol_number?: string,
     *     xml_path?: string,
     *     pdf_danfe_path?: string,
     *     rejection_reason?: string
     * }
     */
    public function emitNFe(Tenant $tenant, FiscalInvoice $invoice): array;

    /**
     * Transmite a NFSe ao provedor fiscal / Prefeitura.
     */
    public function emitNFSe(Tenant $tenant, FiscalInvoice $invoice): array;

    /**
     * Cancela uma Nota Fiscal autorizada.
     */
    public function cancelInvoice(Tenant $tenant, string $accessKey, string $justification): bool;
}
