<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Expulsa a los usuarios con la cuenta desactivada: cierra su sesión y los
 * manda al login con un aviso. Así, desactivar una cuenta surte efecto aunque
 * el usuario tuviera una sesión abierta.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('status', __('Tu cuenta está desactivada. Contacta con el administrador.'));
        }

        return $next($request);
    }
}
