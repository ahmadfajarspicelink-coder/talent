<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test validasi field wajib Partner (R3.2).
 *
 * - Pengiriman data Partner tanpa nama dan tanpa tipe ditolak: response
 *   redirect (302) kembali ke form dengan error validasi untuk 'name' dan
 *   'type', dan tidak ada Partner yang tersimpan.
 * - Sebagai pembanding, pengiriman data lengkap (name + type valid) berhasil
 *   menyimpan Partner.
 */
class PartnerValidationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPartnerAccess(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /** R3.2: submit tanpa nama & tipe → redirect dengan error field wajib, tidak ada Partner tersimpan. */
    public function test_submit_tanpa_nama_dan_tipe_ditolak_dengan_error_field_wajib(): void
    {
        $user = $this->userWithPartnerAccess('admin');

        $response = $this->actingAs($user)
            ->from('/partners')
            ->post('/partners', [
                // name dan type sengaja dikosongkan
                'address' => 'Jl. Contoh No. 1',
                'pic' => 'Budi',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect('/partners');
        $response->assertSessionHasErrors(['name', 'type']);

        $this->assertSame(0, Partner::count());
    }

    /** R3.2: berlaku juga untuk role staff (staff punya akses Modul_Partner). */
    public function test_submit_tanpa_nama_dan_tipe_sebagai_staff_juga_ditolak(): void
    {
        $user = $this->userWithPartnerAccess('staff');

        $response = $this->actingAs($user)
            ->from('/partners')
            ->post('/partners', []);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['name', 'type']);

        $this->assertSame(0, Partner::count());
    }

    /** Pembanding: data lengkap dengan tipe valid berhasil menyimpan Partner. */
    public function test_submit_data_lengkap_berhasil_menyimpan_partner(): void
    {
        $user = $this->userWithPartnerAccess('admin');

        $response = $this->actingAs($user)
            ->from('/partners')
            ->post('/partners', [
                'name' => 'PT Provider Sejahtera',
                'type' => 'provider',
                'address' => 'Jl. Merdeka No. 10',
                'pic' => 'Andi',
                'status' => 'active',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Partner::count());
        $this->assertDatabaseHas('partners', [
            'name' => 'PT Provider Sejahtera',
            'type' => 'provider',
        ]);
    }
}
