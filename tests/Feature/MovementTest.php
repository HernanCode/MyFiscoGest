<?php

namespace Tests\Feature;

use App\Models\Movement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_movement_on_15_may_belongs_to_quarter_2_of_year_2026_and_creates_the_year(): void
    {
        $movement = Movement::create([
            'date' => '2026-05-15',
            'type' => 'income',
            'concept' => 'Servicios de desarrollo',
            'base_amount' => 1000,
            'vat_rate' => 21,
            'irpf_rate' => 0,
        ]);

        $this->assertSame(2, $movement->quarter);
        $this->assertSame(2026, $movement->fiscalYear->year);
        $this->assertDatabaseHas('fiscal_years', ['year' => 2026]);
    }

    public function test_movements_in_the_same_year_reuse_the_existing_fiscal_year(): void
    {
        Movement::create([
            'date' => '2026-05-15',
            'type' => 'income',
            'concept' => 'Servicios de desarrollo',
            'base_amount' => 1000,
        ]);

        Movement::create([
            'date' => '2026-08-10',
            'type' => 'expense',
            'concept' => 'Hosting anual',
            'base_amount' => 120,
        ]);

        $this->assertDatabaseCount('fiscal_years', 1);
        $this->assertSame(3, Movement::where('concept', 'Hosting anual')->first()->quarter);
    }

    #[DataProvider('calculationCases')]
    public function test_vat_irpf_and_total_are_calculated_on_save(
        float $base,
        float $vatRate,
        float $irpfRate,
        string $expectedVat,
        string $expectedIrpf,
        string $expectedTotal,
    ): void {
        $movement = Movement::create([
            'date' => '2026-05-15',
            'type' => 'income',
            'concept' => 'Factura de prueba',
            'base_amount' => $base,
            'vat_rate' => $vatRate,
            'irpf_rate' => $irpfRate,
        ])->refresh();

        $this->assertSame($expectedVat, number_format((float) $movement->vat_amount, 2, '.', ''));
        $this->assertSame($expectedIrpf, number_format((float) $movement->irpf_amount, 2, '.', ''));
        $this->assertSame($expectedTotal, number_format((float) $movement->total_amount, 2, '.', ''));
    }

    public static function calculationCases(): array
    {
        return [
            'IVA 21 %' => [1000.00, 21, 0, '210.00', '0.00', '1210.00'],
            'IVA 10 %' => [1000.00, 10, 0, '100.00', '0.00', '1100.00'],
            'IVA 0 %' => [1000.00, 0, 0, '0.00', '0.00', '1000.00'],
            'IVA 21 % con retención 15 %' => [1000.00, 21, 15, '210.00', '150.00', '1060.00'],
            'IVA 10 % con retención 7 %' => [1000.00, 10, 7, '100.00', '70.00', '1030.00'],
            'Redondeo con céntimos' => [99.99, 21, 0, '21.00', '0.00', '120.99'],
        ];
    }

    public function test_income_and_expense_scopes_filter_by_type(): void
    {
        Movement::create([
            'date' => '2026-05-01', 'type' => 'income', 'concept' => 'Ingreso',
            'base_amount' => 1000,
        ]);

        Movement::create([
            'date' => '2026-05-02', 'type' => 'expense', 'concept' => 'Gasto',
            'base_amount' => 100,
        ]);

        $this->assertSame(1, Movement::income()->count());
        $this->assertSame(1, Movement::expense()->count());
    }
}
