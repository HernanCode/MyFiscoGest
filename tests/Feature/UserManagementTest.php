<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\FiscalYear;
use App\Models\Invitation;
use App\Models\Movement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    private function member(): User
    {
        return User::factory()->create();
    }

    public function test_only_admins_can_reach_the_management_routes(): void
    {
        $target = $this->member();

        $this->actingAs($this->member())->patch(route('users.update', $target))->assertForbidden();
        $this->actingAs($this->member())->post(route('users.reset-password', $target))->assertForbidden();
        $this->actingAs($this->member())->patch(route('users.toggle-status', $target))->assertForbidden();
        $this->actingAs($this->member())->delete(route('users.destroy', $target))->assertForbidden();
    }

    public function test_admin_can_edit_name_and_email(): void
    {
        $admin = $this->admin();
        $target = $this->member();

        $this->actingAs($admin)
            ->patch(route('users.update', $target), ['name' => 'Nombre Nuevo', 'email' => 'nuevo@example.com'])
            ->assertRedirect();

        $target->refresh();
        $this->assertSame('Nombre Nuevo', $target->name);
        $this->assertSame('nuevo@example.com', $target->email);

        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $admin->id,
            'target_user_id' => $target->id,
            'action' => 'user.updated',
        ]);
    }

    public function test_admin_cannot_manage_their_own_account_here(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('users.update', $admin), ['name' => 'X', 'email' => 'x@example.com'])->assertForbidden();
        $this->actingAs($admin)->patch(route('users.toggle-status', $admin))->assertForbidden();
        $this->actingAs($admin)->delete(route('users.destroy', $admin), ['confirm_email' => $admin->email])->assertForbidden();
    }

    public function test_admin_can_deactivate_and_reactivate_a_user(): void
    {
        $admin = $this->admin();
        $target = $this->member();

        $this->actingAs($admin)->patch(route('users.toggle-status', $target))->assertRedirect();
        $this->assertNotNull($target->refresh()->deactivated_at);
        $this->assertFalse($target->isActive());
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'user.deactivated', 'target_user_id' => $target->id]);

        $this->actingAs($admin)->patch(route('users.toggle-status', $target))->assertRedirect();
        $this->assertNull($target->refresh()->deactivated_at);
        $this->assertTrue($target->isActive());
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'user.reactivated', 'target_user_id' => $target->id]);
    }

    public function test_a_deactivated_user_cannot_log_in(): void
    {
        $user = $this->member();
        $user->forceFill(['deactivated_at' => now()])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_deactivated_user_with_an_open_session_is_logged_out(): void
    {
        $user = $this->member();
        $user->forceFill(['deactivated_at' => now()])->save();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_can_generate_a_password_reset_link(): void
    {
        $admin = $this->admin();
        $target = $this->member();

        $this->actingAs($admin)
            ->post(route('users.reset-password', $target))
            ->assertRedirect()
            ->assertSessionHas('reset_link');

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $target->email]);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'user.reset_link', 'target_user_id' => $target->id]);
    }

    public function test_admin_can_delete_a_user_and_their_data(): void
    {
        $admin = $this->admin();
        $target = $this->member();

        Movement::create([
            'user_id' => $target->id,
            'date' => '2026-03-01',
            'type' => 'income',
            'concept' => 'Ingreso',
            'base_amount' => 100,
            'vat_rate' => 21,
            'irpf_rate' => 0,
        ]);

        Invitation::create([
            'email' => $target->email,
            'token' => Invitation::newToken(),
            'invited_by' => $admin->id,
            'expires_at' => now()->addDays(7),
        ]);

        $targetId = $target->id;
        $targetEmail = $target->email;

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target), ['confirm_email' => $targetEmail])
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $targetId]);
        $this->assertDatabaseMissing('movements', ['user_id' => $targetId]);
        $this->assertDatabaseMissing('fiscal_years', ['user_id' => $targetId]);
        $this->assertDatabaseMissing('invitations', ['email' => $targetEmail]);

        // El rastro sobrevive al borrado: la FK queda a null pero la etiqueta se conserva.
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'user.deleted',
            'target_user_id' => null,
            'target_label' => $targetEmail,
        ]);
    }

    public function test_deleting_a_user_requires_the_exact_email(): void
    {
        $admin = $this->admin();
        $target = $this->member();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target), ['confirm_email' => 'otro@example.com'])
            ->assertSessionHasErrors('confirm_email', null, 'userDeletion');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_editing_a_user_rejects_a_duplicate_email(): void
    {
        $admin = $this->admin();
        $target = $this->member();
        $other = $this->member();

        $this->actingAs($admin)
            ->patch(route('users.update', $target), ['name' => 'X', 'email' => $other->email])
            ->assertSessionHasErrors('email');

        $this->assertNotSame($other->email, $target->refresh()->email);
    }
}
