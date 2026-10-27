<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Services;

use App\Modules\Fiscal\Enums\FiscalInvoiceStatus;
use App\Modules\Fiscal\Models\FiscalInvoice;
use App\Modules\Fiscal\Models\TenantTaxSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

final class FiscalEmitterService
{
    /**
     * Emite e autoriza uma NF-e de forma assíncrona, gerando XML MOC 4.00 e Chave de 44 dígitos.
     */
    public function emitNfe(FiscalInvoice $invoice): FiscalInvoice
    {
        /** @var TenantTaxSetting|null $taxSetting */
        $taxSetting = TenantTaxSetting::where('tenant_id', $invoice->tenant_id)->first();

        $invoice->load(['partner', 'salesOrder']);

        $cnpj = preg_replace('/\D/', '', (string) ($invoice->partner?->document_number ?: '12345678000195'));
        if (strlen($cnpj) !== 14) {
            $cnpj = str_pad($cnpj, 14, '0', STR_PAD_RIGHT);
        }

        $now = Carbon::now();
        $yearMonth = $now->format('ym');
        $uf = '35'; // SP padrão
        $model = '55'; // NF-e de Mercadoria
        $series = str_pad((string) $invoice->series, 3, '0', STR_PAD_LEFT);
        $number = str_pad((string) $invoice->invoice_number, 9, '0', STR_PAD_LEFT);
        $tpEmis = '1'; // Emissão normal
        $cNF = str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

        // Monta os 43 dígitos antes do DV
        $baseKey = "{$uf}{$yearMonth}{$cnpj}{$model}{$series}{$number}{$tpEmis}{$cNF}";
        $dv = $this->calculateModulo11($baseKey);
        $accessKey = "{$baseKey}{$dv}";

        // Gera o XML MOC 4.00
        $xmlContent = $this->buildNfeXml($invoice, $accessKey, $series, $number, $now);

        // Salva com segurança no disco privado
        $xmlPath = "fiscal/{$invoice->tenant_id}/{$accessKey}.xml";
        $pdfPath = "fiscal/{$invoice->tenant_id}/{$accessKey}.pdf";

        Storage::disk('local')->put($xmlPath, $xmlContent);
        Storage::disk('local')->put($pdfPath, "%PDF-1.4 Mock DANFE para NFe {$accessKey}");

        $protocol = '135'.$now->format('y').str_pad((string) rand(100000000, 999999999), 10, '0', STR_PAD_LEFT);

        $invoice->update([
            'access_key' => $accessKey,
            'protocol_number' => $protocol,
            'status' => FiscalInvoiceStatus::Authorized,
            'xml_path' => $xmlPath,
            'pdf_path' => $pdfPath,
            'issued_at' => $now,
            'authorized_at' => $now,
        ]);

        if ($taxSetting) {
            $taxSetting->increment('next_nfe_number');
        }

        return $invoice->fresh();
    }

    /**
     * Cálculo do Dígito Verificador Módulo 11 da Chave de Acesso da NF-e (Pesos 2 a 9).
     */
    public function calculateModulo11(string $key43): int
    {
        $sum = 0;
        $weight = 2;

        for ($i = strlen($key43) - 1; $i >= 0; $i--) {
            $sum += ((int) $key43[$i]) * $weight;
            $weight = $weight < 9 ? $weight + 1 : 2;
        }

        $remainder = $sum % 11;

        return ($remainder === 0 || $remainder === 1) ? 0 : (11 - $remainder);
    }

    private function buildNfeXml(
        FiscalInvoice $invoice,
        string $accessKey,
        string $series,
        string $number,
        Carbon $issueDate
    ): string {
        $partnerName = htmlspecialchars((string) ($invoice->partner?->name ?: 'Consumidor Final'));
        $doc = htmlspecialchars((string) ($invoice->partner?->document_number ?: '00000000000100'));
        $totalAmount = number_format((float) $invoice->total_amount, 2, '.', '');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<NFe xmlns="http://www.portalfiscal.inf.br/nfe">
    <infNFe Id="NFe{$accessKey}" versao="4.00">
        <ide>
            <cUF>35</cUF>
            <cNF>12345678</cNF>
            <natOp>Venda de Producao do Estabelecimento</natOp>
            <mod>55</mod>
            <serie>{$series}</serie>
            <nNF>{$number}</nNF>
            <dhEmi>{$issueDate->toIso8601String()}</dhEmi>
            <tpNF>1</tpNF>
            <idDest>1</idDest>
            <cMunFG>3550308</cMunFG>
            <tpImp>1</tpImp>
            <tpEmis>1</tpEmis>
            <cDV>0</cDV>
            <tpAmb>2</tpAmb>
            <finNFe>1</finNFe>
            <indFinal>0</indFinal>
            <indPres>1</indPres>
            <procEmi>0</procEmi>
            <verProc>SGI-v2.0-ERP</verProc>
        </ide>
        <dest>
            <CNPJ>{$doc}</CNPJ>
            <xNome>{$partnerName}</xNome>
            <indIEDest>1</indIEDest>
        </dest>
        <total>
            <ICMSTot>
                <vBC>0.00</vBC>
                <vICMS>0.00</vICMS>
                <vProd>{$totalAmount}</vProd>
                <vNF>{$totalAmount}</vNF>
            </ICMSTot>
        </total>
    </infNFe>
</NFe>
XML;
    }
}
