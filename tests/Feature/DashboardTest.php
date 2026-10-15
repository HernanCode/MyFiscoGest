<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_the_estimates_for_the_selected_quarter(): void
    {
        $user = User::factory()->create();

        Movement::create([
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'type' => 'income',
            'concept' => 'Servicios de desarrollo',
            'base_amount' => 1000,
            'vat_rate' => 21,
            'irpf_rate' => 15,
        ]);

        $this->actingAs($user)
            ->get('/?year=2026&quarter=1')
            ->assertOk()
            ->assertSee('Estimaciones orientativas. Verifica antes de presentar.')
            // Ingresos (base) y resultado
            ->assertSee('1.000,00 €')
            // Gastos deducibles
            ->assertSee('0,00 €')
            // 303 estimado: 210 de IVA repercutido
            ->assertSee('210,00 €')
            // 130 estimado: 20 % de 1000 = 200 - 150 retenido = 50
            ->assertSee('50,00 €')
            ->assertSee('A pagar')
            ->assertSee('A ingresar');
    }

    public function test_dashboard_shows_pending_invoices_and_the_next_filing(): void
    {
        $this->travelTo('2026-10-07 12:00:00');

        $user = User::factory()->create();

        Movement::create([
            'user_id' => $user->id,
            'date' => '2026-05-15',
            'type' => 'income',
            'concept' => 'Consultoría',
            'counterparty' => 'Cliente S.L.',
            'base_amount' => 1000,
            'vat_rate' => 21,
            'irpf_rate' => 0,
        ]);

        $this->actingAs($user)
            ->get('/?year=2026&quarter=2')
            ->assertOk()
            ->assertSee('Caja pendiente')
            ->assertSee('Cliente S.L.')
            ->assertSee('1.210,00 €') // 1000 + 210 de IVA
            ->assertSee('Próximo vencimiento')
            // Próximo vencimiento: pendiente más cercano con fecha >= hoy
            // (el del T1 ya ha vencido y se avisa aparte)
            ->assertSee('Modelo 303 · T3')
            ->assertSee('Importe estimado')
            ->assertSee('presentaciones vencidas sin marcar');
    }

    public function test_dashboard_without_fiscal_years_shows_empty_state(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Aún no hay datos para mostrar.');
    }
}
