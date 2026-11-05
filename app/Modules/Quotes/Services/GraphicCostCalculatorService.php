<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Services;

final class GraphicCostCalculatorService
{
    private const SCALE = 4;

    /**
     * Calculates technical printing and gift production costs with strict Zero-Float bcmath precision.
     *
     * @param array{
     *     quantity: int,
     *     colors_front: int,
     *     colors_back: int,
     *     product_type?: string,
     *     ctp_plate_cost?: string,
     *     substrate_unit_cost?: string,
     *     machine_run_cost_per_thousand?: string,
     *     customization_unit_cost?: string,
     *     finishing_flat_cost?: string,
     *     markup_multiplier?: string
     * } $params
     *
     * @return array{
     *     ctp_plates_count: int,
     *     ctp_cost: string,
     *     substrate_cost: string,
     *     machine_run_cost: string,
     *     customization_cost: string,
     *     finishing_cost: string,
     *     total_estimated_cost: string,
     *     unit_cost: string,
     *     markup_multiplier: string,
     *     unit_price: string,
     *     total_price: string,
     *     margin_amount: string,
     *     margin_percent: string
     * }
     */
    public static function calculate(array $params): array
    {
        $qty = (string) max(1, $params['quantity']);
        $colorsFront = max(0, $params['colors_front']);
        $colorsBack = max(0, $params['colors_back']);
        $platesCount = $colorsFront + $colorsBack;

        // Default unit costs if omitted
        $ctpPlateCost = $params['ctp_plate_cost'] ?? '35.0000'; // R$ 35,00 por chapa CTP gravada
        $substrateUnitCost = $params['substrate_unit_cost'] ?? '0.4500'; // Papel Couché / substrato por unidade
        $machinePerThousand = $params['machine_run_cost_per_thousand'] ?? '40.0000'; // Rodagem de impressora
        $customizationUnitCost = $params['customization_unit_cost'] ?? '0.0000'; // Silk/Laser por peça
        $finishingFlatCost = $params['finishing_flat_cost'] ?? '50.0000'; // Acabamento / guilhotina
        $markup = $params['markup_multiplier'] ?? '1.5000'; // 1.5 = 50% markup

        // 1. CTP Plates Cost: plates * plate_cost
        $ctpCost = bcmul((string) $platesCount, $ctpPlateCost, self::SCALE);

        // 2. Substrate Cost: qty * substrate_unit_cost
        $substrateCost = bcmul($qty, $substrateUnitCost, self::SCALE);

        // 3. Machine Run Cost: (qty / 1000) * machine_rate
        $thousands = bcdiv($qty, '1000', self::SCALE);
        $machineRunCost = bcmul($thousands, $machinePerThousand, self::SCALE);

        // 4. Customization (Brindes): qty * customization_unit_cost
        $customizationCost = bcmul($qty, $customizationUnitCost, self::SCALE);

        // 5. Finishing Flat Cost
        $finishingCost = bcadd($finishingFlatCost, '0.0000', self::SCALE);

        // Total Estimated Cost = CTP + Substrate + Machine + Customization + Finishing
        $step1 = bcadd($ctpCost, $substrateCost, self::SCALE);
        $step2 = bcadd($step1, $machineRunCost, self::SCALE);
        $step3 = bcadd($step2, $customizationCost, self::SCALE);
        $totalCost = bcadd($step3, $finishingCost, self::SCALE);

        // Unit Cost = total_cost / qty
        $unitCost = bcdiv($totalCost, $qty, self::SCALE);

        // Unit Price = unit_cost * markup
        $unitPrice = bcmul($unitCost, $markup, self::SCALE);

        // Total Price = unit_price * qty
        $totalPrice = bcmul($unitPrice, $qty, self::SCALE);

        // Margin Amount = total_price - total_cost
        $marginAmount = bcsub($totalPrice, $totalCost, self::SCALE);

        // Margin Percent = (margin_amount / total_price) * 100
        $marginPercent = bccomp($totalPrice, '0.0000', self::SCALE) > 0
            ? bcmul(bcdiv($marginAmount, $totalPrice, self::SCALE), '100', self::SCALE)
            : '0.0000';

        return [
            'ctp_plates_count' => $platesCount,
            'ctp_cost' => $ctpCost,
            'substrate_cost' => $substrateCost,
            'machine_run_cost' => $machineRunCost,
            'customization_cost' => $customizationCost,
            'finishing_cost' => $finishingCost,
            'total_estimated_cost' => $totalCost,
            'unit_cost' => $unitCost,
            'markup_multiplier' => $markup,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'margin_amount' => $marginAmount,
            'margin_percent' => $marginPercent,
        ];
    }
}
