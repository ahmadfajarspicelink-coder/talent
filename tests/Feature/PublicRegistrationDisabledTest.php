<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifikasi registrasi publik di-disable. Akun baru harus dibuat via
 * User Management module (admin only) — tidak ada self-registration.
 *
 * QW #6 — security hardening: C-01 (open /register) dari audit report.
 */
class PublicRegistrationDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_route_returns_404(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(404);
    }

    public function test_post_register_returns_404(): void
    {
        $response = $this->post('/register', [
            'name' => 'Attacker',
            'email' => 'attacker@evil.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);
        $response->assertStatus(404);

        // Pastikan tidak ada user baru yang terbuat
        $this->assertDatabaseMissing('users', ['email' => 'attacker@evil.com']);
    }

    public function test_named_register_route_not_registered(): void
    {
        $this->expectException(\Symfony\Component\Routing\Exception\RouteNotFoundException::class);
        try {
            route('register');
        } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException $e) {
            throw $e;
        }
    }

    public function test_admin_can_still_create_user_via_user_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'New Staff',
            'email' => 'newstaff@example.com',
            'password' => 'StrongPass123!',
            'role' => 'staff',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'email' => 'newstaff@example.com',
            'role' => 'staff',
        ]);
    }
}
