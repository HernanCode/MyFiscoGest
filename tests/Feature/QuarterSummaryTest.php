<?php

namespace Tests\Feature;

use App\Models\FiscalYear;
use App\Models\Movement;
use App\Services\QuarterSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuarterSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function createMovement(string $type, array $overrides = []): Movement
    {
        return Movement::create(array_merge([
            'date' => '2026-01-15',
            'type' => $type,
            'concept' => 'Movimiento de prueba',
            'base_amount' => 1000,
            'vat_rate' => 21,
            'irpf_rate' => 0,
            'deductible_pct' => 100,
        ], $overrides));
    }

    private function fiscalYearId(): int
    {
        return FiscalYear::where('year', 2026)->firstOrFail()->id;
    }

    public function test_t1_with_only_income(): void
    {
        $this->createMovement('income', ['base_amount' => 1000, 'irpf_rate' => 15]);
        $this->createMovement('income', ['base_amount' => 2000, 'irpf_rate' => 15]);

        $summary = QuarterSummary::for($this->fiscalYearId(), 1);

        $this->assertSame(3000.0, (float) $summary['income_base']);
        $this->assertSame(0.0, (float) $summary['expense_base']);
        $this->assertSame(3000.0, (float) $summary['net_income']);
        $this->assertSame(630.0, (float) $summary['vat_charged']);
        $this->assertSame(0.0, (float) $summary['vat_paid']);
        $this->assertSame(630.0, (float) $summary['modelo_303']);
        $this->assertSame(150.0, (float) $summary['modelo_130']);
    }

    public function test_t2_depends_on_amount_paid_in_t1(): void
    {
        $year = $this->createMovement('income', ['date' => '2026-01-15', 'base_amount' => 1000, 'irpf_rate' => 15])->fiscalYear;

        // Ya se pagó el 130 de T1 (20 % de 1000 = 200 - 150 retenido = 50).
        $year->filings()->where('model', '130')->where('quarter', 1)->update(['amount_paid' => 50]);

        $this->createMovement('income', ['date' => '2026-05-15', 'base_amount' => 1000, 'irpf_rate' => 15]);

        $summary = QuarterSummary::for($this->fiscalYearId(), 2);

        // 303 solo mira el trimestre 2.
        $this->assertSame(210.0, (float) $summary['vat_charged']);
        $this->assertSame(210.0, (float) $summary['modelo_303']);
        $this->assertSame(0.0, (float) $summary['vat_paid']);

        // 130 acumulado: 20 % de 2000 = 400 - 300 retenido - 50 ya pagado = 50.
        $this->assertSame(2000.0, (float) $summary['income_base']);
        $this->assertSame(50.0, (float) $summary['modelo_130']);
        $this->assertSame(50.0, (float) $summary['previous_130']);
    }

    public function test_expense_with_deductible_pct_below_100(): void
    {
        $this->createMovement('expense', ['base_amount' => 1000, 'deductible_pct' => 30]);
        $this->createMovement('income', ['base_amount' => 1000]);

        $summary = QuarterSummary::for($this->fiscalYearId(), 1);

        // Solo se deduce el 30 % de base e IVA soportado.
        $this->assertSame(300.0, (float) $summary['expense_base']);
        $this->assertSame(63.0, (float) $summary['vat_paid']);
        $this->assertSame(147.0, (float) $summary['modelo_303']); // 210 - 63
        $this->assertSame(140.0, (float) $summary['modelo_130']); // 20 % de 700
    }

    public function test_vat_paid_greater_than_vat_charged_results_in_negative_303(): void
    {
        $this->createMovement('expense', ['base_amount' => 1000, 'deductible_pct' => 100]);

        $summary = QuarterSummary::for($this->fiscalYearId(), 1);

        $this->assertSame(-210.0, (float) $summary['modelo_303']);
    }

    public function test_year_without_movements_returns_zeros(): void
    {
        FiscalYear::create(['year' => 2027]);

        $summary = QuarterSummary::for(FiscalYear::where('year', 2027)->firstOrFail()->id, 1);

        $this->assertSame(0.0, (float) $summary['income_base']);
        $this->assertSame(0.0, (float) $summary['expense_base']);
        $this->assertSame(0.0, (float) $summary['net_income']);
        $this->assertSame(0.0, (float) $summary['vat_charged']);
        $this->assertSame(0.0, (float) $summary['vat_paid']);
        $this->assertSame(0.0, (float) $summary['modelo_303']);
        $this->assertSame(0.0, (float) $summary['modelo_130']);
        $this->assertSame(0.0, (float) $summary['previous_130']);
    }

    public function test_withheld_amounts_greater_than_20_percent_force_130_to_zero(): void
    {
        $this->createMovement('income', ['base_amount' => 1000, 'irpf_rate' => 30]);

        $summary = QuarterSummary::for($this->fiscalYearId(), 1);

        $this->assertSame(1000.0, (float) $summary['net_income']);
        $this->assertSame(0.0, (float) $summary['modelo_130']); // 200 - 300 = -100 → 0
    }

    public function test_for_filing_returns_the_amount_that_corresponds_to_its_model(): void
    {
        $this->createMovement('income', ['base_amount' => 1000, 'irpf_rate' => 15]);

        $year = FiscalYear::where('year', 2026)->firstOrFail();
        $filing = fn (string $model) => $year->filings()
            ->where('quarter', 1)->where('model', $model)->firstOrFail();

        // 303: IVA repercutido (210). 130: 200 - 150 retenido = 50.
        $this->assertSame(210.0, QuarterSummary::forFiling($filing('303')));
        $this->assertSame(50.0, QuarterSummary::forFiling($filing('130')));
    }
}
