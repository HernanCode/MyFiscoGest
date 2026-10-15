<?php

namespace Tests\Feature;

use App\Models\FiscalYear;
use App\Models\Movement;
use App\Models\QuarterFiling;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilingTest extends TestCase
{
    use RefreshDatabase;

    private function filing(int $quarter, string $model): QuarterFiling
    {
        return FiscalYear::where('year', 2026)->firstOrFail()
            ->filings()
            ->where('quarter', $quarter)
            ->where('model', $model)
            ->firstOrFail();
    }

    public function test_guest_cannot_access_or_change_filings(): void
    {
        $filing = FiscalYear::create(['year' => 2026])->filings()->firstOrFail();

        $this->get(route('filings.index'))->assertRedirect('/login');
        $this->patch(route('filings.mark-filed', $filing))->assertRedirect('/login');
        $this->patch(route('filings.reopen', $filing))->assertRedirect('/login');
    }

    public function test_presentaciones_lists_the_filings_of_the_selected_year(): void
    {
        $user = User::factory()->create();
        FiscalYear::create(['year' => 2026, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('filings.index', ['year' => 2026]))
            ->assertOk()
            ->assertSee('303')
            ->assertSee('130')
            ->assertSee(__('IVA trimestral'))
            ->assertSee(__('IRPF fraccionado'))
            ->assertSee('20 abr 2026')
            ->assertSee('30 ene 2027');
    }

    public function test_presentaciones_shows_the_estimate_of_each_model(): void
    {
        $user = User::factory()->create();

        Movement::create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'type' => 'income',
            'concept' => 'Factura de servicios',
            'base_amount' => 1000,
            'vat_rate' => 21,
            'irpf_rate' => 15,
        ]);

        $this->actingAs($user)
            ->get(route('filings.index', ['year' => 2026]))
            ->assertOk()
            // 303 del T1: 210 de IVA repercutido.
            ->assertSee('210,00 €')
            // 130 del T1: 20 % de 1000 - 150 retenido = 50.
            ->assertSee('50,00 €');
    }

    public function test_marking_a_filing_as_filed_stores_status_amount_and_date(): void
    {
        $user = User::factory()->create();
        FiscalYear::create(['year' => 2026, 'user_id' => $user->id]);
        $filing = $this->filing(1, '303');

        $this->actingAs($user)
            ->patch(route('filings.mark-filed', $filing), ['amount_paid' => '210.00'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $filing->refresh();

        $this->assertSame('filed', $filing->status);
        $this->assertSame(210.0, (float) $filing->amount_paid);
        $this->assertNotNull($filing->filed_at);
        $this->assertSame(today()->toDateString(), $filing->filed_at->toDateString());
    }

    public function test_marking_without_an_amount_leaves_it_empty(): void
    {
        $user = User::factory()->create();
        FiscalYear::create(['year' => 2026, 'user_id' => $user->id]);
        $filing = $this->filing(1, '303');

        $this->actingAs($user)
            ->patch(route('filings.mark-filed', $filing))
            ->assertSessionHasNoErrors();

        $filing->refresh();

        $this->assertSame('filed', $filing->status);
        $this->assertNull($filing->amount_paid);
    }

    public function test_the_paid_amount_must_be_numeric(): void
    {
        $user = User::factory()->create();
        FiscalYear::create(['year' => 2026, 'user_id' => $user->id]);
        $filing = $this->filing(1, '303');

        $this->actingAs($user)
            ->patch(route('filings.mark-filed', $filing), ['amount_paid' => 'no-es-un-número'])
            ->assertSessionHasErrors('amount_paid');

        $this->assertSame('pending', $filing->refresh()->status);
    }

    public function test_reopening_a_filing_clears_the_filed_state(): void
    {
        $user = User::factory()->create();
        FiscalYear::create(['year' => 2026, 'user_id' => $user->id]);
        $filing = $this->filing(1, '303');
        $filing->update(['status' => 'filed', 'filed_at' => '2026-04-18', 'amount_paid' => 210]);

        $this->actingAs($user)
            ->patch(route('filings.reopen', $filing))
            ->assertRedirect();

        $filing->refresh();

        $this->assertSame('pending', $filing->status);
        $this->assertNull($filing->filed_at);
        $this->assertNull($filing->amount_paid);
    }

    public function test_a_user_cannot_touch_another_users_filing(): void
    {
        $owner = User::factory()->create();
        FiscalYear::create(['year' => 2026, 'user_id' => $owner->id]);
        $filing = $this->filing(1, '303');

        $this->actingAs(User::factory()->create())
            ->patch(route('filings.mark-filed', $filing), ['amount_paid' => '10'])
            ->assertNotFound();

        $this->assertSame('pending', $filing->refresh()->status);
    }
}
