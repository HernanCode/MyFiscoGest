<?php

namespace Tests\Feature;

use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscalYearTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_fiscal_year_generates_exactly_8_filings(): void
    {
        $year = FiscalYear::create(['year' => 2026]);

        $this->assertCount(8, $year->filings);
        $this->assertDatabaseCount('quarter_filings', 8);
    }

    public function test_filings_have_correct_quarters_models_and_due_dates(): void
    {
        $year = FiscalYear::create(['year' => 2026]);

        $expected = [
            [1, '303', '2026-04-20'],
            [1, '130', '2026-04-20'],
            [2, '303', '2026-07-20'],
            [2, '130', '2026-07-20'],
            [3, '303', '2026-10-20'],
            [3, '130', '2026-10-20'],
            [4, '303', '2027-01-30'],
            [4, '130', '2027-01-30'],
        ];

        $actual = $year->filings()
            ->orderBy('id')
            ->get()
            ->map(fn ($filing) => [
                $filing->quarter,
                $filing->model,
                $filing->due_date->format('Y-m-d'),
            ])
            ->all();

        $this->assertSame($expected, $actual);

        $year->filings->each(function ($filing) {
            $this->assertSame('pending', $filing->status);
        });
    }

    public function test_fiscal_year_is_unique_per_user(): void
    {
        $user = User::factory()->create();

        FiscalYear::create(['year' => 2026, 'user_id' => $user->id]);

        $this->expectException(QueryException::class);

        FiscalYear::create(['year' => 2026, 'user_id' => $user->id]);
    }

    public function test_two_users_can_each_have_the_same_year(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        FiscalYear::create(['year' => 2026, 'user_id' => $a->id]);
        FiscalYear::create(['year' => 2026, 'user_id' => $b->id]);

        $this->assertDatabaseHas('fiscal_years', ['year' => 2026, 'user_id' => $a->id]);
        $this->assertDatabaseHas('fiscal_years', ['year' => 2026, 'user_id' => $b->id]);
        $this->assertSame(2, FiscalYear::where('year', 2026)->count());
    }
}
