<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MovementCrudTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        return User::factory()->create();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'income',
            'date' => '2026-05-15',
            'concept' => 'Servicios de desarrollo',
            'counterparty' => 'Cliente S.L.',
            'base_amount' => 1000,
            'vat_rate' => 21,
            'irpf_rate' => 15,
            'payment_status' => 'pending',
        ], $overrides);
    }

    private function movementWithInvoice(): Movement
    {
        Storage::fake('local');

        $movement = Movement::create([
            'user_id' => $this->actingUser()->id,
            'date' => '2026-05-15',
            'type' => 'income',
            'concept' => 'Servicios de desarrollo',
            'base_amount' => 1000,
            'vat_rate' => 21,
            'irpf_rate' => 15,
        ]);

        Storage::disk('local')->put('invoices/2026/T2/factura.pdf', 'bytes-del-pdf');
        $movement->forceFill(['invoice_path' => 'invoices/2026/T2/factura.pdf'])->save();

        return $movement;
    }

    public function test_guest_cannot_access_an_invoice(): void
    {
        $movement = $this->movementWithInvoice();

        $this->get(route('movements.invoice', $movement))->assertRedirect('/login');

        $this->actingAs($movement->user)
            ->get(route('movements.invoice', $movement))
            ->assertOk();
    }

    public function test_upload_stores_invoice_in_the_expected_path_and_delete_removes_the_file(): void
    {
        Storage::fake('local');
        $user = $this->actingUser();

        $this->actingAs($user)
            ->post(route('movements.store'), $this->validPayload([
                'invoice' => UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf'),
            ]))
            ->assertRedirect(route('movements.index'))
            ->assertSessionHasNoErrors();

        $movement = Movement::firstOrFail();

        $this->assertNotNull($movement->invoice_path);
        $this->assertStringStartsWith('invoices/2026/T2/', $movement->invoice_path);
        Storage::disk('local')->assertExists($movement->invoice_path);

        $this->actingAs($user)
            ->delete(route('movements.destroy', $movement))
            ->assertRedirect(route('movements.index'));

        Storage::disk('local')->assertMissing($movement->invoice_path);
        $this->assertDatabaseMissing('movements', ['id' => $movement->id]);
    }

    public function test_validation_rejects_disallowed_files_and_negative_amounts(): void
    {
        $user = $this->actingUser();

        $this->actingAs($user)
            ->post(route('movements.store'), $this->validPayload([
                'invoice' => UploadedFile::fake()->create('notas.txt', 10, 'text/plain'),
            ]))
            ->assertSessionHasErrors('invoice');

        $this->actingAs($user)
            ->post(route('movements.store'), $this->validPayload(['base_amount' => -100]))
            ->assertSessionHasErrors('base_amount');

        $this->assertDatabaseCount('movements', 0);
    }

    public function test_changing_the_date_moves_the_invoice_to_the_new_quarter_folder(): void
    {
        Storage::fake('local');
        $user = $this->actingUser();

        $movement = Movement::create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'type' => 'expense',
            'concept' => 'Hosting anual',
            'base_amount' => 120,
            'vat_rate' => 21,
            'deductible_pct' => 100,
        ]);

        Storage::disk('local')->put('invoices/2026/T1/1_factura.pdf', 'bytes');
        $movement->forceFill(['invoice_path' => 'invoices/2026/T1/1_factura.pdf'])->save();

        $this->actingAs($user)
            ->put(route('movements.update', $movement), [
                'date' => '2026-05-15',
                'concept' => 'Hosting anual',
                'base_amount' => 120,
                'vat_rate' => 21,
                'deductible_pct' => 100,
                'payment_status' => 'pending',
            ])
            ->assertRedirect(route('movements.index'))
            ->assertSessionHasNoErrors();

        $movement->refresh();

        $this->assertSame(2, $movement->quarter);
        Storage::disk('local')->assertMissing('invoices/2026/T1/1_factura.pdf');
        Storage::disk('local')->assertExists('invoices/2026/T2/1_factura.pdf');
        $this->assertSame('invoices/2026/T2/1_factura.pdf', $movement->invoice_path);
    }

    public function test_marking_as_paid_sets_paid_at_to_today(): void
    {
        $this->actingAs($this->actingUser())
            ->post(route('movements.store'), $this->validPayload(['payment_status' => 'paid']));

        $movement = Movement::firstOrFail();

        $this->assertSame('paid', $movement->payment_status);
        $this->assertSame(now()->toDateString(), $movement->paid_at->toDateString());
    }

    public function test_storing_an_expense_forces_irpf_to_zero_and_income_forces_deductible_to_100(): void
    {
        $this->actingAs($this->actingUser())
            ->post(route('movements.store'), $this->validPayload([
                'type' => 'expense',
                'deductible_pct' => 30,
                'irpf_rate' => 15,
            ]))
            ->assertSessionHasNoErrors();

        $movement = Movement::firstOrFail();

        $this->assertSame(0.0, (float) $movement->irpf_rate);
        $this->assertSame(30, $movement->deductible_pct);
        $this->assertSame(2, $movement->quarter);
    }

    public function test_movements_list_shows_rows_with_calculated_totals(): void
    {
        $user = $this->actingUser();

        Movement::create([
            'user_id' => $user->id,
            'date' => '2026-05-15',
            'type' => 'income',
            'concept' => 'Servicios de desarrollo',
            'base_amount' => 1000,
            'vat_rate' => 21,
            'irpf_rate' => 15,
        ]);

        $this->actingAs($user)
            ->get(route('movements.index'))
            ->assertOk()
            ->assertSee('Servicios de desarrollo')
            ->assertSee('Nuevo ingreso')
            ->assertSee('Nuevo gasto')
            ->assertSee('1.060,00 €'); // 1000 + 210 - 150
    }
}
