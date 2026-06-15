<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test kontrol akses berbasis role dan ketidaktersediaan layanan (R2).
 *
 * - Staff diblokir dari modul Finance, Client, dan User Management (HTTP 403,
 *   pesan "akses ditolak") sementara tetap bisa mengakses Order dan Partner (R2.4).
 * - Admin bisa mengakses seluruh modul (R2.2).
 * - Modul yang ditandai tidak tersedia ditolak dengan HTTP 503 + pesan
 *   "layanan tidak tersedia", bahkan untuk role yang seharusnya diizinkan (R2.6).
 */
class ModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'staff']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** R2.4: Staff diblokir dari modul terlarang dengan HTTP 403. */
    public function test_staff_diblokir_dari_modul_terlarang(): void
    {
        $staff = $this->staff();

        foreach (['/clients', '/finance/orders', '/users'] as $path) {
            $response = $this->actingAs($staff)->get($path);

            $response->assertStatus(403);
            $this->assertStringContainsString('akses ditolak', $response->getContent());
        }
    }

    /** R2.3: Staff tetap bisa mengakses modul yang diizinkan (Order & Partner). */
    public function test_staff_bisa_akses_modul_yang_diizinkan(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->get('/partners')->assertOk();
        $this->actingAs($staff)->get('/orders')->assertOk();
    }

    /** R2.2: Admin bisa mengakses seluruh modul. */
    public function test_admin_bisa_akses_semua_modul(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/clients')->assertOk();
        $this->actingAs($admin)->get('/finance/orders')->assertOk();
        $this->actingAs($admin)->get('/users')->assertOk();
    }

    /** R2.6: Modul yang tidak tersedia ditolak dengan HTTP 503. */
    public function test_modul_tidak_tersedia_menolak_dengan_503(): void
    {
        config(['modules.unavailable' => ['order']]);

        $staff = $this->staff();

        $response = $this->actingAs($staff)->get('/orders');

        $response->assertStatus(503);
    }
}
