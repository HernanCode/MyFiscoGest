<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\RecurringMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecurringMovementTest extends TestCase
{
    use RefreshDatabase;

    private ?User $user = null;

    private function actingUser(): User
    {
        return $this->user ??= User::factory()->create();
    }

    private function movementPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'expense',
            'date' => '2026-01-10',
            'concept' => 'Cuota de autónomos',
            'base_amount' => 90,
            'vat_rate' => 0,
            'deductible_pct' => 100,
            'payment_status' => 'pending',
        ], $overrides);
    }

    private function template(array $overrides = []): RecurringMovement
    {
        return RecurringMovement::factory()->create(array_merge([
            'user_id' => $this->actingUser()->id,
            'type' => 'expense',
            'concept' => 'Cuota de autónomos',
            'base_amount' => 90,
            'vat_rate' => 0,
            'deductible_pct' => 100,
            'start_date' => '2026-01-01',
            'day_of_month' => 5,
            'next_date' => '2026-01-05',
        ], $overrides));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_cannot_generate_recurring_movements(): void
    {
        $this->get(route('movements.recurring.index'))->assertRedirect('/login');
        $this->post(route('movements.recurring.generate'))->assertRedirect('/login');
    }

    public function test_saving_a_movement_with_the_recurring_flag_creates_a_template(): void
    {
        $this->actingAs($this->actingUser())
            ->post(route('movements.store'), $this->movementPayload([
                'recurring' => '1',
                'recurring_day' => '5',
            ]))
            ->assertRedirect(route('movements.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('recurring_movements', 1);

        $movement = Movement::firstOrFail();
        $recurring = RecurringMovement::firstOrFail();

        $this->assertSame($recurring->id, $movement->recurring_movement_id);
        $this->assertSame('Cuota de autónomos', $recurring->concept);
        $this->assertSame(5, $recurring->day_of_month);
        // El mes del movimiento original ya está cubierto: toca el mes siguiente.
        $this->assertSame('2026-02-05', $recurring->next_date->toDateString());
        $this->assertSame('2026-01-10', $recurring->start_date->toDateString());
    }

    public function test_saving_without_the_flag_does_not_create_a_template(): void
    {
        $this->actingAs($this->actingUser())
            ->post(route('movements.store'), $this->movementPayload(['recurring' => '0', 'recurring_day' => '']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('recurring_movements', 0);
        $this->assertNull(Movement::firstOrFail()->recurring_movement_id);
    }

    public function test_recurring_day_is_required_when_the_flag_is_marked(): void
    {
        $this->actingAs($this->actingUser())
            ->post(route('movements.store'), $this->movementPayload(['recurring' => '1']))
            ->assertSessionHasErrors('recurring_day');

        $this->assertDatabaseCount('movements', 0);

        $this->actingAs($this->actingUser())
            ->post(route('movements.store'), $this->movementPayload(['recurring' => '1', 'recurring_day' => '99']))
            ->assertSessionHasErrors('recurring_day');
    }

    public function test_editing_a_movement_can_turn_it_into_a_recurring_one(): void
    {
        $movement = Movement::create([
            'user_id' => $this->actingUser()->id,
            'date' => '2026-01-10',
            'type' => 'expense',
            'concept' => 'Cuota de autónomos',
            'base_amount' => 90,
            'vat_rate' => 0,
            'deductible_pct' => 100,
        ]);

        $this->assertDatabaseCount('recurring_movements', 0);

        // El formulario de edición ofrece la casilla si aún no es recurrente.
        $user = $this->actingUser();
        $this->actingAs($user)
            ->get(route('movements.edit', $movement))
            ->assertOk()
            ->assertSee(__('Repetir cada mes'));

        $this->actingAs($user)
            ->put(route('movements.update', $movement), [
                'date' => '2026-01-10',
                'concept' => 'Cuota de autónomos',
                'base_amount' => 90,
                'vat_rate' => 0,
                'deductible_pct' => 100,
                'payment_status' => 'pending',
                'recurring' => '1',
                'recurring_day' => '5',
            ])
            ->assertRedirect(route('movements.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('recurring_movements', 1);

        $recurring = RecurringMovement::firstOrFail();

        $this->assertSame($recurring->id, $movement->refresh()->recurring_movement_id);
        // El mes del movimiento original ya está cubierto: toca febrero.
        $this->assertSame('2026-02-05', $recurring->next_date->toDateString());

        // Si ya es recurrente, el formulario muestra el aviso con el enlace.
        $this->actingAs($user)
            ->get(route('movements.edit', $movement))
            ->assertOk()
            ->assertSee(__('Se repite cada mes'))
            ->assertDontSee(__('Repetir cada mes'));
    }

    public function test_editing_without_the_flag_keeps_the_movement_as_it_is(): void
    {
        $movement = Movement::create([
            'user_id' => $this->actingUser()->id,
            'date' => '2026-01-10',
            'type' => 'expense',
            'concept' => 'Compra de material',
            'base_amount' => 40,
            'vat_rate' => 21,
            'deductible_pct' => 100,
        ]);

        $this->actingAs($this->actingUser())
            ->put(route('movements.update', $movement), [
                'date' => '2026-01-10',
                'concept' => 'Compra de material',
                'base_amount' => 45,
                'vat_rate' => 21,
                'deductible_pct' => 100,
                'payment_status' => 'pending',
                'recurring' => '0',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('recurring_movements', 0);
        $this->assertNull($movement->refresh()->recurring_movement_id);
    }

    public function test_movement_screens_show_the_tabs_to_switch_between_them(): void
    {
        $user = $this->actingUser();

        $this->actingAs($user)
            ->get(route('movements.index'))
            ->assertOk()
            ->assertSee(__('Recurrentes'));

        $this->actingAs($user)
            ->get(route('movements.recurring.index'))
            ->assertOk()
            ->assertSee(__('Todos'));
    }

    public function test_generating_creates_every_due_month_and_skips_future_ones(): void
    {
        Carbon::setTestNow('2026-03-20 10:00:00');
        $this->template();

        $this->actingAs($this->actingUser())
            ->post(route('movements.recurring.generate'))
            ->assertRedirect(route('movements.recurring.index'));

        $this->assertDatabaseCount('movements', 3);

        $this->assertSame(
            ['2026-01-05', '2026-02-05', '2026-03-05'],
            Movement::orderBy('date')->pluck('date')->map->toDateString()->all()
        );

        // Los movimientos nacen pendientes, sin factura y sin retención.
        $movement = Movement::firstOrFail();
        $this->assertSame('pending', $movement->payment_status);
        $this->assertNull($movement->paid_at);
        $this->assertNull($movement->invoice_path);
        $this->assertSame(90.0, (float) $movement->total_amount);

        // La próxima pendiente es la del mes que viene, aún no generada.
        $this->assertSame('2026-04-05', RecurringMovement::firstOrFail()->next_date->toDateString());
    }

    public function test_generating_twice_does_not_duplicate_months(): void
    {
        Carbon::setTestNow('2026-03-20 10:00:00');
        $this->template();

        $this->actingAs($this->actingUser())->post(route('movements.recurring.generate'));
        $this->actingAs($this->actingUser())->post(route('movements.recurring.generate'));

        $this->assertDatabaseCount('movements', 3);
    }

    public function test_day_31_falls_back_to_the_last_day_of_shorter_months(): void
    {
        Carbon::setTestNow('2026-03-15 10:00:00');
        $this->template(['day_of_month' => 31, 'next_date' => '2026-01-31']);

        $this->actingAs($this->actingUser())->post(route('movements.recurring.generate'));

        $this->assertSame(
            ['2026-01-31', '2026-02-28'],
            Movement::orderBy('date')->pluck('date')->map->toDateString()->all()
        );

        // Marzo sí tiene 31: el día indicado no se pierde tras el ajuste.
        $this->assertSame('2026-03-31', RecurringMovement::firstOrFail()->next_date->toDateString());
    }

    public function test_a_recurrence_stops_after_its_end_date(): void
    {
        Carbon::setTestNow('2026-06-20 10:00:00');
        $this->template(['ends_on' => '2026-02-20']);

        $this->actingAs($this->actingUser())->post(route('movements.recurring.generate'));

        $this->assertSame(
            ['2026-01-05', '2026-02-05'],
            Movement::orderBy('date')->pluck('date')->map->toDateString()->all()
        );
    }

    public function test_pausing_a_recurrence_stops_generation_and_reactivating_resumes_from_the_future(): void
    {
        Carbon::setTestNow('2026-04-20 10:00:00');
        $recurring = $this->template();

        $this->actingAs($this->actingUser())
            ->patch(route('movements.recurring.toggle', $recurring))
            ->assertRedirect(route('movements.recurring.index'));

        $this->assertFalse($recurring->refresh()->active);

        $this->actingAs($this->actingUser())->post(route('movements.recurring.generate'));
        $this->assertDatabaseCount('movements', 0);

        // Al reactivar, continúa desde el próximo día indicado (sin meses perdidos).
        $this->actingAs($this->actingUser())->patch(route('movements.recurring.toggle', $recurring));

        $recurring->refresh();
        $this->assertTrue($recurring->active);
        $this->assertSame('2026-05-05', $recurring->next_date->toDateString());

        // Y solo genera cuando ese mes toca de verdad.
        Carbon::setTestNow('2026-05-10 10:00:00');
        $this->actingAs($this->actingUser())->post(route('movements.recurring.generate'));
        $this->assertSame(['2026-05-05'], Movement::pluck('date')->map->toDateString()->all());
    }

    public function test_deleting_a_template_keeps_the_generated_movements(): void
    {
        Carbon::setTestNow('2026-02-20 10:00:00');
        $recurring = $this->template();

        $this->actingAs($this->actingUser())->post(route('movements.recurring.generate'));
        $this->assertDatabaseCount('movements', 2);

        $this->actingAs($this->actingUser())
            ->delete(route('movements.recurring.destroy', $recurring))
            ->assertRedirect(route('movements.recurring.index'));

        // El 20 de febrero ya se habían generado enero y febrero.
        $this->assertDatabaseCount('recurring_movements', 0);
        $this->assertDatabaseCount('movements', 2);
        $this->assertNull(Movement::firstOrFail()->recurring_movement_id);
    }

    public function test_updating_the_template_day_recalculates_the_next_generation(): void
    {
        Carbon::setTestNow('2026-01-08 10:00:00');
        $recurring = $this->template();

        $this->actingAs($this->actingUser())
            ->put(route('movements.recurring.update', $recurring), [
                'concept' => 'Cuota de autónomos',
                'base_amount' => 95,
                'vat_rate' => 0,
                'deductible_pct' => 100,
                'day_of_month' => 20,
                'active' => '1',
            ])
            ->assertRedirect(route('movements.recurring.index'))
            ->assertSessionHasNoErrors();

        $recurring->refresh();

        $this->assertSame(20, $recurring->day_of_month);
        $this->assertSame(95.0, (float) $recurring->base_amount);
        // Primer día 20 que aún no ha pasado (hoy es 8 de enero).
        $this->assertSame('2026-01-20', $recurring->next_date->toDateString());
    }

    public function test_generated_movement_cannot_collide_with_another_of_the_same_recurrence(): void
    {
        Carbon::setTestNow('2026-02-20 10:00:00');
        $recurring = $this->template();

        $this->actingAs($this->actingUser())->post(route('movements.recurring.generate'));

        $movement = Movement::orderBy('date')->firstOrFail();

        $this->actingAs($this->actingUser())
            ->put(route('movements.update', $movement), [
                // Fecha del otro movimiento generado en esta misma recurrencia.
                'date' => '2026-02-05',
                'concept' => $movement->concept,
                'base_amount' => $movement->base_amount,
                'vat_rate' => 0,
                'deductible_pct' => 100,
                'payment_status' => 'pending',
            ])
            ->assertSessionHasErrors('date');
    }

    public function test_the_recurring_screen_lists_templates(): void
    {
        $this->template(['concept' => 'Cuota de autónomos']);
        $this->template(['concept' => 'Alquiler del despacho', 'day_of_month' => 1]);

        $this->actingAs($this->actingUser())
            ->get(route('movements.recurring.index'))
            ->assertOk()
            ->assertSee('Cuota de autónomos')
            ->assertSee('Alquiler del despacho')
            ->assertSee('Generar ahora');

        $this->actingAs($this->actingUser())
            ->get(route('movements.recurring.edit', RecurringMovement::firstOrFail()))
            ->assertOk()
            ->assertSee('Día del mes')
            ->assertSee('Editar recurrencia');
    }
}
