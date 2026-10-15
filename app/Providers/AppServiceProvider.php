<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Contraseñas de al menos 12 caracteres (perfil, reset e invitaciones).
        Password::defaults(fn () => Password::min(12));

        // En producción damos por hecho que hay HTTPS delante (Docker/nginx):
        // los enlaces se generan con https y la cookie de sesión va marcada
        // como segura (salvo que se indique lo contrario en el .env).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            if (config('session.secure') === null) {
                config(['session.secure' => true]);
            }
        }
    }
}
