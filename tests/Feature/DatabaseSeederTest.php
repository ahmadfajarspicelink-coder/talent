<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifikasi DatabaseSeeder tidak insert default password lemah dan refuse
 * to run di production environment.
 *
 * QW #1 — security hardening: ganti plaintext 'password' dengan env-based
 * random password, dan tambah production guard.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_admin_and_staff_with_hashed_passwords(): void
    {
        (new DatabaseSeeder())->run();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'staff@example.com',
            'role' => 'staff',
        ]);

        // Password TIDAK boleh plaintext 'password' (raw string length = 8)
        $admin = \App\Models\User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertNotEquals('password', $admin->password);
        // Hash bcrypt selalu panjang 60 char dan mulai dengan $2y$
        $this->assertStringStartsWith('$2y$', $admin->password);
        $this->assertSame(60, strlen($admin->password));
    }

    public function test_seeder_password_is_never_default_password_string(): void
    {
        (new DatabaseSeeder())->run();

        // Critical: tidak boleh ada user dengan password plaintext 'password'
        // atau apapun yang password_verify('password', ...) return true.
        $users = \App\Models\User::all();
        foreach ($users as $user) {
            $this->assertFalse(
                password_verify('password', $user->password),
                "User {$user->email} punya password yang match dengan default 'password'! Critical security fail."
            );
        }
    }

    public function test_seeder_refuses_to_run_in_production_environment(): void
    {
        // Simulasikan environment production
        $this->app->detectEnvironment(fn () => 'production');

        $userCountBefore = \App\Models\User::count();
        (new DatabaseSeeder())->run();
        $userCountAfter = \App\Models\User::count();

        $this->assertSame(
            $userCountBefore,
            $userCountAfter,
            'DatabaseSeeder harus refuse jalan di production environment.'
        );
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'staff@example.com']);
    }
}
