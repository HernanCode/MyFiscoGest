<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\FiscalYear;
use App\Models\Movement;
use App\Services\QuarterSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $fiscalYears = FiscalYear::forUser($request->user()->id)->orderByDesc('year')->get();

        // Año seleccionado: por defecto el actual si existe; si no, el más reciente.
        $selectedYear = $fiscalYears->firstWhere('year', now()->year)
            ?? $fiscalYears->sortByDesc('year')->first();

        if ($request->filled('year')) {
            $selectedYear = $fiscalYears->firstWhere('year', $request->integer('year')) ?? $selectedYear;
        }

        $quarter = (int) ceil(now()->month / 3);
        if ($request->filled('quarter')) {
            $quarter = $request->integer('quarter');
        }
        $quarter = max(1, min(4, $quarter));

        $summary = $selectedYear ? QuarterSummary::for($selectedYear->id, $quarter) : self::zeroSummary();

        $yearId = $selectedYear?->id;
        $today = now()->startOfDay();

        // Retenciones de IRPF acumuladas hasta el trimestre (nota de la tarjeta 130).
        $retentions = $yearId
            ? Movement::where('fiscal_year_id', $yearId)->income()
                ->where('quarter', '<=', $quarter)->sum('irpf_amount')
            : 0;

        // ---- Trimestre anterior (para la línea de nivel del resultado) ----
        $previousSummary = null;
        $previousLabel = null;

        if ($selectedYear) {
            if ($quarter > 1) {
                $previousSummary = QuarterSummary::for($selectedYear->id, $quarter - 1);
                $previousLabel = 'T'.($quarter - 1);
            } else {
                $previousYear = FiscalYear::forUser($request->user()->id)
                    ->where('year', '<', $selectedYear->year)
                    ->orderByDesc('year')->first();
                if ($previousYear) {
                    $previousSummary = QuarterSummary::for($previousYear->id, 4);
                    $previousLabel = 'T4 '.$previousYear->year;
                }
            }
        }

        // ---- Ingresos SOLO del trimestre seleccionado (tarjeta A) y delta vs trimestre anterior ----
        $quarterIncome = 0.0;
        $incomeDelta = null;
        if ($yearId) {
            $quarterIncome = (float) Movement::where('fiscal_year_id', $yearId)->income()
                ->where('quarter', $quarter)->sum('base_amount');

            $previousQuarterIncome = null;
            if ($quarter > 1) {
                $previousQuarterIncome = (float) Movement::where('fiscal_year_id', $yearId)->income()
                    ->where('quarter', $quarter - 1)->sum('base_amount');
            } else {
                $previousYear = FiscalYear::forUser($request->user()->id)
                    ->where('year', '<', $selectedYear->year)
                    ->orderByDesc('year')->first();
                if ($previousYear) {
                    $previousQuarterIncome = (float) Movement::where('fiscal_year_id', $previousYear->id)->income()
                        ->where('quarter', 4)->sum('base_amount');
                }
            }

            if ($previousQuarterIncome !== null && $previousQuarterIncome > 0) {
                $incomeDelta = round(($quarterIncome - $previousQuarterIncome) / $previousQuarterIncome * 100, 1);
            }
        }

        // ---- Gastos y margen del trimestre seleccionado (tarjeta A, mismo periodo) ----
        $quarterExpense = 0.0;
        if ($yearId) {
            $quarterExpense = (float) Movement::where('fiscal_year_id', $yearId)->expense()
                ->where('quarter', $quarter)
                ->selectRaw('COALESCE(SUM(base_amount * deductible_pct / 100), 0) as t')
                ->value('t');
        }
        $quarterMargin = $quarterIncome - $quarterExpense;
        $quarterMarginPct = $quarterIncome > 0 ? round($quarterMargin / $quarterIncome * 100, 1) : null;

        // ---- Series mensuales del año seleccionado ----
        $months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $monthly = [
            'labels' => $months,
            'income' => array_fill(0, 12, 0.0),
            'expense' => array_fill(0, 12, 0.0),
            'net' => array_fill(0, 12, 0.0),
        ];

        if ($yearId) {
            Movement::where('fiscal_year_id', $yearId)
                ->get(['date', 'type', 'base_amount', 'deductible_pct'])
                ->each(function (Movement $movement) use (&$monthly) {
                    $i = $movement->date->month - 1;
                    if ($movement->type === 'income') {
                        $monthly['income'][$i] += (float) $movement->base_amount;
                    } else {
                        $monthly['expense'][$i] += (float) $movement->base_amount * (float) $movement->deductible_pct / 100;
                    }
                });

            $accumulated = 0.0;
            for ($i = 0; $i < 12; $i++) {
                $accumulated += $monthly['income'][$i] - $monthly['expense'][$i];
                $monthly['net'][$i] = round($accumulated, 2);
            }
        }

        // ---- Próximo vencimiento: presentación pendiente con fecha igual o posterior a hoy ----
        // Se agrupan TODAS las presentaciones que vencen ese mismo día (303 y 130),
        // para mostrar el importe total a pagar en la fecha, no solo un modelo.
        $nextFiling = null;
        $nextPayment = null;

        if ($selectedYear) {
            $nextFiling = $selectedYear->filings()
                ->where('status', 'pending')
                ->whereDate('due_date', '>=', $today->toDateString())
                ->orderBy('due_date')->orderBy('id')
                ->first();

            if ($nextFiling) {
                $dueFilings = $selectedYear->filings()
                    ->where('status', 'pending')
                    ->whereDate('due_date', $nextFiling->due_date->toDateString())
                    ->get()
                    ->keyBy('model');

                // Orden estable 303 (IVA) → 130 (IRPF), independiente del id.
                $items = collect(['303', '130'])
                    ->filter(fn (string $model) => $dueFilings->has($model))
                    ->map(function (string $model) use ($dueFilings, $selectedYear) {
                        $filing = $dueFilings->get($model);
                        $summary = QuarterSummary::for($selectedYear->id, $filing->quarter);

                        return [
                            'model' => $model,
                            'quarter' => $filing->quarter,
                            'estimate' => (float) ($model === '130' ? $summary['modelo_130'] : $summary['modelo_303']),
                        ];
                    })
                    ->values();

                $nextPayment = [
                    'due_date' => $nextFiling->due_date,
                    'items' => $items,
                    // A pagar = solo los importes positivos; lo negativo es crédito a compensar.
                    'pay_total' => (float) $items->sum(fn (array $item) => max(0, $item['estimate'])),
                    'credit_total' => (float) $items->sum(fn (array $item) => max(0, -$item['estimate'])),
                ];
            }
        }

        // ---- Vencimientos pendientes que ya han pasado (aviso aparte) ----
        $overdueFilings = $selectedYear
            ? $selectedYear->filings()->where('status', 'pending')
                ->whereDate('due_date', '<', $today->toDateString())
                ->orderBy('due_date')->get()
            : collect();

        // ---- Facturas de ingresos pendientes de cobro del año seleccionado ----
        $pendingInvoices = $selectedYear
            ? $selectedYear->movements()->income()
                ->where('payment_status', 'pending')
                ->orderByDesc('date')->orderByDesc('id')
                ->limit(5)->get()
            : collect();

        // ---- Caja pendiente: lo que entra y lo que sale del año seleccionado ----
        $pendingReceivable = $selectedYear
            ? (float) $selectedYear->movements()->income()->where('payment_status', 'pending')->sum('total_amount')
            : 0.0;
        $pendingPayable = $selectedYear
            ? (float) $selectedYear->movements()->expense()->where('payment_status', 'pending')->sum('total_amount')
            : 0.0;
        $cashNet = $pendingReceivable - $pendingPayable;

        $pendingCashItems = $selectedYear
            ? $selectedYear->movements()
                ->where('payment_status', 'pending')
                ->orderByDesc('date')->orderByDesc('id')
                ->limit(6)->get()
            : collect();

        // ---- Checklist "ponte al día": datos que conviene cerrar ----
        $movementsWithoutInvoiceCount = $selectedYear
            ? $selectedYear->movements()->whereNull('invoice_path')->count()
            : 0;
        $expensesWithoutCategoryCount = $selectedYear
            ? $selectedYear->movements()->expense()->whereNull('category_id')->count()
            : 0;
        $overdueCollectionsCount = $selectedYear
            ? $selectedYear->movements()->income()->where('payment_status', 'pending')
                ->whereDate('date', '<', $today->toDateString())->count()
            : 0;

        // ---- Últimos movimientos del año seleccionado ----
        $recentMovements = $selectedYear
            ? $selectedYear->movements()->orderByDesc('date')->orderByDesc('id')->limit(5)->get()
            : collect();

        // ---- Por categoría: ingresos del trimestre seleccionado (5 filas) ----
        $categoryRows = $this->categoryBreakdown($yearId, $quarter);

        return view('dashboard', [
            'fiscalYears' => $fiscalYears,
            'selectedYear' => $selectedYear,
            'quarter' => $quarter,
            'summary' => $summary,
            'retentions' => $retentions,
            'quarterIncome' => $quarterIncome,
            'previousSummary' => $previousSummary,
            'previousLabel' => $previousLabel,
            'incomeDelta' => $incomeDelta,
            'quarterExpense' => $quarterExpense,
            'quarterMargin' => $quarterMargin,
            'quarterMarginPct' => $quarterMarginPct,
            'monthly' => $monthly,
            'categoryRows' => $categoryRows,
            'nextFiling' => $nextFiling,
            'nextPayment' => $nextPayment,
            'overdueFilings' => $overdueFilings,
            'pendingInvoices' => $pendingInvoices,
            'pendingReceivable' => $pendingReceivable,
            'pendingPayable' => $pendingPayable,
            'cashNet' => $cashNet,
            'pendingCashItems' => $pendingCashItems,
            'movementsWithoutInvoiceCount' => $movementsWithoutInvoiceCount,
            'expensesWithoutCategoryCount' => $expensesWithoutCategoryCount,
            'overdueCollectionsCount' => $overdueCollectionsCount,
            'recentMovements' => $recentMovements,
        ]);
    }

    /**
     * Ingresos del trimestre agrupados por categoría (máx. 5, por importe).
     */
    private function categoryBreakdown(?int $yearId, int $quarter): Collection
    {
        if (! $yearId) {
            return collect();
        }

        $rows = Movement::where('fiscal_year_id', $yearId)->income()
            ->where('quarter', $quarter)
            ->selectRaw('COALESCE(category_id, 0) as category_id, SUM(base_amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $names = Category::whereIn('id', $rows->pluck('category_id')->reject(0))
            ->pluck('name', 'id');

        $grandTotal = (float) $rows->sum('total');

        return $rows->map(fn ($row) => [
            'name' => $row->category_id ? ($names[$row->category_id] ?? __('Sin categoría')) : __('Sin categoría'),
            'total' => (float) $row->total,
            'pct' => $grandTotal > 0 ? round($row->total / $grandTotal * 100) : 0,
        ]);
    }

    private static function zeroSummary(): array
    {
        return [
            'income_base' => 0,
            'expense_base' => 0,
            'net_income' => 0,
            'vat_charged' => 0,
            'vat_paid' => 0,
            'modelo_303' => 0,
            'retentions' => 0,
            'modelo_130' => 0,
            'previous_130' => 0,
        ];
    }
}
