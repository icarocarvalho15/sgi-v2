<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Enums;

enum KardexMovementType: string
{
    case InPurchase = 'IN_PURCHASE';
    case InProduction = 'IN_PRODUCTION';
    case InAdjustment = 'IN_ADJUSTMENT';
    case InTransfer = 'IN_TRANSFER';

    case OutProduction = 'OUT_PRODUCTION';
    case OutSale = 'OUT_SALE';
    case OutAdjustment = 'OUT_ADJUSTMENT';
    case OutTransfer = 'OUT_TRANSFER';
    case OutScrap = 'OUT_SCRAP';

    /**
     * Retorna se a movimentação é de entrada no estoque.
     */
    public function isInbound(): bool
    {
        return match ($this) {
            self::InPurchase,
            self::InProduction,
            self::InAdjustment,
            self::InTransfer => true,
            default => false,
        };
    }

    /**
     * Retorna se a movimentação é de saída no estoque.
     */
    public function isOutbound(): bool
    {
        return ! $this->isInbound();
    }

    /**
     * Rótulo legível para UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::InPurchase => 'Entrada por Compra',
            self::InProduction => 'Entrada por Produção (PA)',
            self::InAdjustment => 'Ajuste de Estoque (+)',
            self::InTransfer => 'Transferência Recebida',
            self::OutProduction => 'Consumo em Produção (OP)',
            self::OutSale => 'Saída por Venda',
            self::OutAdjustment => 'Ajuste de Estoque (-)',
            self::OutTransfer => 'Transferência Enviada',
            self::OutScrap => 'Perda / Sucata',
        };
    }
}
