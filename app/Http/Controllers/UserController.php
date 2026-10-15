<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\View\View;

/**
 * Gestión de usuarios de la instancia. Solo el dueño (administrador) puede
 * entrar: el registro público está desactivado y las cuentas se crean por
 * invitación.
 */
class UserController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return view('users.index', [
            'users' => User::orderBy('id')->get(),
            'invitations' => Invitation::whereNull('accepted_at')->latest()->get(),
        ]);
    }
}
