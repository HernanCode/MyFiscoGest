<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Movement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiUserTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    private function movement(User $owner, string $concept): Movement
    {
        return Movement::create([
            'user_id' => $owner->id,
            'date' => '2026-03-01',
            'type' => 'income',
            'concept' => $concept,
            'base_amount' => 100,
            'vat_rate' => 21,
            'irpf_rate' => 0,
        ]);
    }

    public function test_data_is_isolated_between_users(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->movement($a, 'Ingreso de A');

        $this->actingAs($a)->get(route('movements.index'))->assertOk()->assertSee('Ingreso de A');
        $this->actingAs($b)->get(route('movements.index'))->assertOk()->assertDontSee('Ingreso de A');
    }

    public function test_a_user_cannot_open_another_users_movement(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $movement = $this->movement($a, 'Privado');

        $this->actingAs($b)->get(route('movements.edit', $movement))->assertNotFound();
    }

    public function test_only_admins_can_manage_users(): void
    {
        $this->actingAs(User::factory()->create())->get(route('users.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('users.index'))->assertOk();
    }

    public function test_only_admins_can_create_invitations(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('invitations.store'))
            ->assertForbidden();

        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_an_invitation_creates_a_user_and_logs_them_in(): void
    {
        $this->actingAs($this->admin())
            ->post(route('invitations.store'), ['email' => 'amigo@example.com'])
            ->assertRedirect();

        $invitation = Invitation::firstOrFail();
        $this->assertNull($invitation->accepted_at);

        // El invitado abre el enlace y crea su cuenta.
        $this->get(route('invitations.show', $invitation->token))->assertOk();

        $this->post(route('invitations.accept', $invitation->token), [
            'name' => 'Amigo',
            'email' => 'amigo@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'amigo@example.com', 'is_admin' => false]);
        $this->assertNotNull($invitation->refresh()->accepted_at);
    }

    public function test_an_invitation_cannot_be_used_twice(): void
    {
        $this->actingAs($this->admin())->post(route('invitations.store'), []);
        $invitation = Invitation::firstOrFail();

        $this->post(route('invitations.accept', $invitation->token), [
            'name' => 'Uno',
            'email' => 'uno@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertRedirect(route('dashboard'));

        $this->post(route('invitations.accept', $invitation->token), [
            'name' => 'Dos',
            'email' => 'dos@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertForbidden();
    }
}
