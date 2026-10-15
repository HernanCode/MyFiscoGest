<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use App\Models\QuarterFiling;
use App\Services\QuarterSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FilingController extends Controller
{
    public function index(Request $request): View
    {
        $fiscalYears = FiscalYear::forUser($request->user()->id)->orderByDesc('year')->get();

        // Año seleccionado: por año o por id en el query string, si no el actual.
        $requestYear = $request->integer('year');
        $selectedYear = $fiscalYears->firstWhere('year', $requestYear)
            ?? $fiscalYears->firstWhere('id', $requestYear)
            ?? $fiscalYears->firstWhere('year', now()->year)
            ?? $fiscalYears->first();

        // Los 8 vencimientos agrupados por trimestre, con su importe estimado.
        $filingsByQuarter = collect();

        if ($selectedYear) {
            $summaries = [];

            $filingsByQuarter = $selectedYear->filings()
                ->orderBy('quarter')
                ->orderBy('model')
                ->get()
                ->groupBy('quarter')
                ->map(function (Collection $quarterFilings) use ($selectedYear, &$summaries) {
                    $quarter = $quarterFilings->first()->quarter;
                    $summary = $summaries[$quarter] ??= QuarterSummary::for($selectedYear->id, $quarter);

                    return $quarterFilings->map(fn (QuarterFiling $filing) => [
                        'filing' => $filing,
                        'estimate' => (float) ($filing->model === '303'
                            ? $summary['modelo_303']
                            : $summary['modelo_130']),
                    ]);
                });
        }

        return view('filings.index', [
            'fiscalYears' => $fiscalYears,
            'selectedYear' => $selectedYear,
            'filingsByQuarter' => $filingsByQuarter,
        ]);
    }

    /**
     * Marca un vencimiento como presentado, con el importe realmente pagado.
     */
    public function markFiled(Request $request, QuarterFiling $filing): RedirectResponse
    {
        $this->authorizeOwner($filing);

        $data = $request->validate([
            'amount_paid' => ['nullable', 'numeric'],
        ]);

        $filing->update([
            'status' => 'filed',
            'filed_at' => now()->toDateString(),
            'amount_paid' => $data['amount_paid'] ?? null,
        ]);

        return back()->with('status', __('Presentación :model del T:quarter marcada como presentada.', [
            'model' => $filing->model,
            'quarter' => $filing->quarter,
        ]));
    }

    /**
     * Devuelve un vencimiento presentado al estado pendiente.
     */
    public function reopen(QuarterFiling $filing): RedirectResponse
    {
        $this->authorizeOwner($filing);

        $filing->update([
            'status' => 'pending',
            'filed_at' => null,
            'amount_paid' => null,
        ]);

        return back()->with('status', __('Presentación :model del T:quarter reabierta.', [
            'model' => $filing->model,
            'quarter' => $filing->quarter,
        ]));
    }
}
