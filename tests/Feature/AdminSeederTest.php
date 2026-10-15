<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sobrescribe una variable de entorno en todas las fuentes que lee Laravel
     * (dotenv consulta $_SERVER, $_ENV y getenv).
     */
    private function setEnv(string $name, string $value): void
    {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    /**
     * @param  array{name: string, email: string, password: string}  $credentials
     */
    private function seedAdmin(array $credentials): void
    {
        foreach ($credentials as $key => $value) {
            $this->setEnv('ADMIN_'.strtoupper($key), $value);
        }

        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeder_creates_admin_from_env(): void
    {
        $this->seedAdmin([
            'name' => 'Samuel',
            'email' => 'seed-admin@example.com',
            'password' => 'seed-secret-password',
        ]);

        $user = User::where('email', 'seed-admin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Samuel', $user->name);
        $this->assertTrue(Hash::check('seed-secret-password', $user->password));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_seeder_does_not_fail_without_env_credentials(): void
    {
        foreach (['ADMIN_NAME', 'ADMIN_EMAIL', 'ADMIN_PASSWORD'] as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_can_login_with_seeded_credentials(): void
    {
        $this->seedAdmin([
            'name' => 'Samuel',
            'email' => 'seed-admin@example.com',
            'password' => 'seed-secret-password',
        ]);

        $response = $this->post('/login', [
            'email' => 'seed-admin@example.com',
            'password' => 'seed-secret-password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
