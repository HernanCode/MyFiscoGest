<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\QuarterFiling;

/**
 * Resumen trimestral de un año fiscal (sección 4 de docs/spec.md).
 *
 * - Modelo 303 (IVA): IVA repercutido de los ingresos del trimestre menos el
 *   IVA soportado deducible de los gastos del trimestre. Puede ser negativo
 *   (a compensar).
 * - Modelo 130 (IRPF): rendimiento neto ACUMULADO desde enero hasta el
 *   trimestre, por 20 %, menos retenciones acumuladas y menos lo ya pagado en
 *   modelos 130 de trimestres anteriores. Nunca menor que cero.
 */
class QuarterSummary
{
    public static function for(int $yearId, int $quarter): array
    {
        $q = Movement::where('fiscal_year_id', $yearId);

        // ---- Modelo 303 (IVA): solo el trimestre ----
        $vatCharged = (clone $q)->income()->where('quarter', $quarter)->sum('vat_amount');
        $vatPaid = (clone $q)->expense()->where('quarter', $quarter)
            ->selectRaw('COALESCE(SUM(vat_amount * deductible_pct / 100), 0) as t')
            ->value('t');
        $vat303 = $vatCharged - $vatPaid;

        // ---- Modelo 130 (IRPF): acumulado desde enero hasta este trimestre ----
        $incomeBase = (clone $q)->income()->where('quarter', '<=', $quarter)->sum('base_amount');
        $expenseBase = (clone $q)->expense()->where('quarter', '<=', $quarter)
            ->selectRaw('COALESCE(SUM(base_amount * deductible_pct / 100), 0) as t')
            ->value('t');
        $netIncome = $incomeBase - $expenseBase;

        $withheld = (clone $q)->income()->where('quarter', '<=', $quarter)->sum('irpf_amount');

        $previous130 = QuarterFiling::where('fiscal_year_id', $yearId)
            ->where('model', '130')->where('quarter', '<', $quarter)
            ->sum('amount_paid');

        $irpf130 = max(0, ($netIncome * 0.20) - $withheld - $previous130);

        return [
            'income_base' => $incomeBase,
            'expense_base' => $expenseBase,
            'net_income' => $netIncome,
            'vat_charged' => $vatCharged,
            'vat_paid' => $vatPaid,
            'modelo_303' => $vat303,   // negativo = a compensar/devolver
            'modelo_130' => $irpf130,
            'previous_130' => (float) $previous130, // pagos fraccionados del 130 ya abonados
        ];
    }

    /**
     * Importe estimado que corresponde a un vencimiento concreto (303 o 130).
     *
     * Centraliza la correspondencia modelo → cifra para que la pantalla de
     * presentaciones no la dupliquen.
     */
    public static function forFiling(QuarterFiling $filing): float
    {
        $summary = self::for($filing->fiscal_year_id, $filing->quarter);

        return (float) ($filing->model === '303' ? $summary['modelo_303'] : $summary['modelo_130']);
    }
}
