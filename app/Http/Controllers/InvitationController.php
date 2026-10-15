<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Altas por invitación: el dueño genera un enlace y se lo pasa a quien quiera
 * dar acceso. No depende del correo saliente (que puede no estar configurado).
 */
class InvitationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
        ]);

        Invitation::create([
            'email' => $data['email'] ?? null,
            'token' => Invitation::newToken(),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(7),
        ]);

        return back()->with('status', __('Invitación creada. Copia el enlace y compártelo.'));
    }

    public function destroy(Invitation $invitation): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $invitation->delete();

        return back()->with('status', __('Invitación revocada.'));
    }

    public function show(string $token): View|RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->first();

        if (! $invitation || ! $invitation->isValid()) {
            return redirect()->route('login')->with('status', __('Esta invitación ya no es válida.'));
        }

        return view('invitations.accept', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->first();

        abort_unless($invitation && $invitation->isValid(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->email_verified_at = now();
        $user->save();

        $invitation->update(['accepted_at' => now()]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('status', __('¡Bienvenido! Tu cuenta está lista.'));
    }
}
