<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature: talent-secreet-isp-broker
 * Autentikasi pengguna (Requirement 1): login valid, kredensial salah,
 * logout, proteksi haluan tanpa login, dan hashing password.
 */
class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * R1.1: Email & password cocok -> sesi login dibuat, redirect ke dashboard.
     */
    public function test_valid_login_authenticates_and_redirects_to_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => 'rahasia-kuat',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'rahasia-kuat',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * R1.2: Kredensial salah -> login ditolak, tetap guest, ada error pada email.
     */
    public function test_invalid_credentials_are_rejected_with_error(): void
    {
        $user = User::factory()->create([
            'password' => 'rahasia-kuat',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-salah',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * R1.3: Pengguna login melakukan logout -> sesi berakhir, redirect.
     * Breeze default mengarahkan ke '/'.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /**
     * R1.4: Pengunjung belum login mengakses halaman terproteksi -> redirect ke login.
     */
    public function test_unauthenticated_access_redirects_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    /**
     * R1.5: Password disimpan dalam bentuk hash (bukan plaintext).
     */
    public function test_password_is_stored_hashed(): void
    {
        $plaintext = 'rahasia-kuat';

        $user = User::factory()->create([
            'password' => $plaintext,
        ]);

        $stored = $user->fresh()->password;

        $this->assertNotSame($plaintext, $stored);
        $this->assertTrue(Hash::check($plaintext, $stored));
    }
}
