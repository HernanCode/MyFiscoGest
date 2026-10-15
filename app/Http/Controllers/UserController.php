<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\AdminAuditLog;
use App\Models\Invitation;
use App\Models\Movement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Gestión de usuarios de la instancia. Solo el dueño (administrador) puede
 * entrar: el registro público está desactivado y las cuentas se crean por
 * invitación.
 *
 * El administrador gestiona CUENTAS (nombre, correo, estado, contraseña y
 * baja), nunca el contenido fiscal de otros usuarios: el aislamiento entre
 * usuarios se mantiene intacto.
 */
class UserController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return view('users.index', [
            'users' => User::orderBy('id')->get(),
            'invitations' => Invitation::whereNull('accepted_at')->latest()->get(),
            'activities' => AdminAuditLog::with('actor')->orderByDesc('id')->limit(30)->get(),
        ]);
    }

    /**
     * Corrige el nombre y el correo de una cuenta. Si cambia el correo, el
     * usuario deberá usarlo la próxima vez para iniciar sesión.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorizeManagement($user);

        $data = $request->validated();
        $changes = [];

        if ($user->name !== $data['name']) {
            $changes['name'] = ['from' => $user->name, 'to' => $data['name']];
        }

        if ($user->email !== $data['email']) {
            $changes['email'] = ['from' => $user->email, 'to' => $data['email']];
        }

        $user->update($data);

        AdminAuditLog::record($request->user(), $user, 'user.updated', $changes);

        return back()->with('status', __('Datos de :name actualizados.', ['name' => $user->name]));
    }

    /**
     * Genera un enlace de restablecimiento de contraseña (se muestra una sola
     * vez para que el administrador se lo pase al usuario por su canal).
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManagement($user);

        $token = Password::broker()->createToken($user);

        AdminAuditLog::record($request->user(), $user, 'user.reset_link');

        return back()
            ->with('status', __('Enlace de restablecimiento generado para :name.', ['name' => $user->name]))
            ->with('reset_link', route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]));
    }

    /**
     * Desactiva o reactiva una cuenta. Desactivar corta el acceso (y las
     * sesiones abiertas) pero conserva todos los datos.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManagement($user);

        if ($user->isActive()) {
            $this->ensureNotLastAdmin($user);

            $user->forceFill(['deactivated_at' => now()])->save();
            $this->forgetSessions($user);

            AdminAuditLog::record($request->user(), $user, 'user.deactivated');

            return back()->with('status', __('Cuenta de :name desactivada.', ['name' => $user->name]));
        }

        $user->forceFill(['deactivated_at' => null])->save();

        AdminAuditLog::record($request->user(), $user, 'user.reactivated');

        return back()->with('status', __('Cuenta de :name reactivada.', ['name' => $user->name]));
    }

    /**
     * Elimina una cuenta y todos sus datos (borrado permanente). Requiere que
     * el administrador escriba el correo exacto para confirmar.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManagement($user);
        $this->ensureNotLastAdmin($user);

        if (strtolower((string) $request->input('confirm_email')) !== strtolower($user->email)) {
            return back()
                ->withInput()
                ->with('delete_user_id', $user->id)
                ->withErrors(
                    ['confirm_email' => __('Escribe el correo exacto para confirmar la eliminación.')],
                    'userDeletion',
                );
        }

        $label = $user->email;
        $meta = [
            'fiscal_years' => $user->fiscalYears()->count(),
            'movements' => Movement::forUser($user->id)->count(),
        ];

        // Registramos ANTES de borrar: la clave foránea nullOnDelete conserva la
        // entrada de auditoría pero pone target_user_id a null; target_label
        // mantiene la identidad del usuario eliminado.
        AdminAuditLog::record($request->user(), $user, 'user.deleted', $meta);

        // Las invitaciones pendientes dirigidas a su correo dejan de valer.
        Invitation::whereNull('accepted_at')->where('email', $user->email)->delete();

        $user->delete();

        return back()->with('status', __('Cuenta de :name eliminada.', ['name' => $label]));
    }

    /**
     * El administrador gestiona cuentas ajenas; la suya se edita desde el
     * perfil (evita confusiones y el bloqueo accidental de la instancia).
     */
    private function authorizeManagement(User $user): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        abort_if(auth()->id() === $user->id, 403, __('Gestiona tu propia cuenta desde tu perfil.'));
    }

    /**
     * La instancia no puede quedarse sin nadie que pueda invitar usuarios.
     */
    private function ensureNotLastAdmin(User $user): void
    {
        abort_if(
            $user->isAdmin() && User::where('is_admin', true)->count() <= 1,
            403,
            __('No puedes dejar la instancia sin administrador.'),
        );
    }

    /**
     * Cierra las sesiones abiertas del usuario (incluido el "recuérdame").
     */
    private function forgetSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $user->forceFill(['remember_token' => null])->save();
    }
}
