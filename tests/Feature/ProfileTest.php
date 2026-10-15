<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\RecurringMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_deleting_an_account_also_deletes_its_data(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $movement = Movement::create([
            'user_id' => $user->id,
            'date' => '2026-03-01',
            'type' => 'income',
            'concept' => 'Factura',
            'base_amount' => 100,
            'vat_rate' => 21,
            'irpf_rate' => 0,
        ]);
        Storage::disk('local')->put('invoices/2026/T1/factura.pdf', 'x');
        $movement->forceFill(['invoice_path' => 'invoices/2026/T1/factura.pdf'])->save();

        RecurringMovement::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('movements', 0);
        $this->assertDatabaseCount('fiscal_years', 0);
        $this->assertDatabaseCount('quarter_filings', 0);
        $this->assertDatabaseCount('recurring_movements', 0);
        Storage::disk('local')->assertMissing('invoices/2026/T1/factura.pdf');
    }

    public function test_the_last_admin_cannot_delete_their_account(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $response = $this
            ->actingAs($admin)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password']);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($admin->fresh());
    }
}
