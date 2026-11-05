<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Fiscal;

use App\Core\Tenancy\Models\Tenant;
use App\Modules\Integrations\Models\FiscalInvoice;

class MockFiscalProvider implements FiscalProviderInterface
{
    public function emitNFe(Tenant $tenant, FiscalInvoice $invoice): array
    {
        // Gera chave de acesso simulada de 44 dígitos
        $uf = '35'; // SP
        $aamm = now()->format('ym');
        $cnpjDigits = preg_replace('/\D/', '', $tenant->subdomain) ?: '12345678000195';
        $cnpjFormatted = str_pad(substr($cnpjDigits, 0, 14), 14, '0', STR_PAD_LEFT);
        $mod = '55';
        $serie = str_pad((string) $invoice->series, 3, '0', STR_PAD_LEFT);
        $nNF = str_pad((string) ($invoice->invoice_number ?? 1), 9, '0', STR_PAD_LEFT);
        $tpEmis = '1';
        $cNF = str_pad(substr(md5($invoice->id), 0, 8), 8, '0', STR_PAD_LEFT);
        $cDV = '7';

        $accessKey = "{$uf}{$aamm}{$cnpjFormatted}{$mod}{$serie}{$nNF}{$tpEmis}{$cNF}{$cDV}";
        $protocol = '13526' . str_pad((string) rand(100000000, 999999999), 10, '0', STR_PAD_LEFT);

        return [
            'success' => true,
            'status' => 'authorized',
            'access_key' => $accessKey,
            'protocol_number' => $protocol,
            'xml_path' => "tenants/{$tenant->id}/fiscal/nfe/" . now()->format('Y-m') . "/{$accessKey}.xml",
            'pdf_danfe_path' => "tenants/{$tenant->id}/fiscal/nfe/" . now()->format('Y-m') . "/{$accessKey}.pdf",
        ];
    }

    public function emitNFSe(Tenant $tenant, FiscalInvoice $invoice): array
    {
        $protocol = 'NFSE-' . now()->format('Ymd') . '-' . substr(md5($invoice->id), 0, 6);

        return [
            'success' => true,
            'status' => 'authorized',
            'protocol_number' => $protocol,
            'xml_path' => "tenants/{$tenant->id}/fiscal/nfse/" . now()->format('Y-m') . "/{$protocol}.xml",
            'pdf_danfe_path' => "tenants/{$tenant->id}/fiscal/nfse/" . now()->format('Y-m') . "/{$protocol}.pdf",
        ];
    }

    public function cancelInvoice(Tenant $tenant, string $accessKey, string $justification): bool
    {
        return true;
    }
}
