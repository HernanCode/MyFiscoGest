<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Crea el usuario administrador (dueño de la instancia) y las categorías.
     *
     * Los datos del admin se leen de ADMIN_NAME, ADMIN_EMAIL y ADMIN_PASSWORD
     * del .env. Si faltan, no se crea ningún usuario y avisa por consola.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $name = env('ADMIN_NAME');
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $name || ! $email || ! $password) {
            $this->command?->warn(
                'ADMIN_NAME, ADMIN_EMAIL y ADMIN_PASSWORD no están definidos en el .env. No se creó ningún usuario.'
            );

            return;
        }

        // firstOrCreate: si el usuario ya existe no le cambiamos la contraseña al
        // volver a sembrar (por ejemplo, en cada arranque del contenedor).
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $user->forceFill(['is_admin' => true])->save();
    }
}
