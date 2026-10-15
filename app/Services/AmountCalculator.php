<?php

namespace App\Services;

/**
 * Cálculo de importes de un movimiento (base + IVA − retención IRPF).
 *
 * Compartido por el hook de `Movement` (al guardar) y por el accessor de
 * `RecurringMovement` (para previsualizar lo que costará cada mes).
 */
class AmountCalculator
{
    /**
     * Acepta null porque los movimientos de prueba y los importes aún no
     * informados llegan como null; se trata como cero, igual que antes.
     *
     * @return array{vat: float, irpf: float, total: float}
     */
    public static function totals(float|string|int|null $baseAmount, float|string|int|null $vatRate, float|string|int|null $irpfRate): array
    {
        $base = (float) $baseAmount;
        $vat = round($base * (float) $vatRate / 100, 2);
        $irpf = round($base * (float) $irpfRate / 100, 2);

        return [
            'vat' => $vat,
            'irpf' => $irpf,
            'total' => round($base + $vat - $irpf, 2),
        ];
    }
}
