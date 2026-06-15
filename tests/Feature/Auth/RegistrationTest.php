<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifikasi bahwa registrasi publik di-disable (QW #6, C-01).
 *
 * Test ini REPLACES Breeze's default RegistrationTest karena project
 * Talent Secreet tidak mengizinkan self-registration. Akun baru dibuat
 * via User Management module (admin only) — lihat
 * {@see \Tests\Feature\PublicRegistrationDisabledTest}.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_returns_404(): void
    {
        // /register harus 404 karena route dihapus dari routes/auth.php.
        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_new_users_cannot_register_through_public_form(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(404);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
