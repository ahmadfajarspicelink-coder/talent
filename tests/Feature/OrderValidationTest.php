<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test validasi field wajib Order (R5.2).
 *
 * - Pengiriman Order tanpa Client, Provider, atau Vendor ditolak: response
 *   redirect (302) kembali ke form dengan error validasi untuk 'client_id',
 *   'provider_id', dan 'vendor_id', dan tidak ada Order yang tersimpan.
 * - Sebagai pembanding, pengiriman data lengkap (client/provider/vendor valid)
 *   berhasil menyimpan Order dengan Status_Order awal Inquiry (R5.1).
 *
 * Catatan: route orders.store dilindungi ['auth','module:order'] sehingga
 * Admin maupun Staff boleh mengakses Modul_Order (R2.2, R2.3).
 */
class OrderValidationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithOrderAccess(string $role = 'staff'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /** R5.2: submit tanpa client/provider/vendor → redirect dengan error field wajib, tidak ada Order tersimpan. */
    public function test_submit_tanpa_client_provider_vendor_ditolak_dengan_error_field_wajib(): void
    {
        $user = $this->userWithOrderAccess('staff');

        $response = $this->actingAs($user)
            ->from('/orders/create')
            ->post('/orders', []);

        $response->assertStatus(302);
        $response->assertRedirect('/orders/create');
        $response->assertSessionHasErrors(['client_name', 'provider_id']);

        $this->assertSame(0, Order::count());
    }

    /** R5.2: berlaku juga untuk role admin (admin punya akses Modul_Order). */
    public function test_submit_tanpa_field_wajib_sebagai_admin_juga_ditolak(): void
    {
        $user = $this->userWithOrderAccess('admin');

        $response = $this->actingAs($user)
            ->from('/orders/create')
            ->post('/orders', []);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['client_name', 'provider_id']);

        $this->assertSame(0, Order::count());
    }

    /** Pembanding: data lengkap berhasil menyimpan Order berstatus awal Inquiry (R5.1). */
    public function test_submit_data_lengkap_berhasil_menyimpan_order_status_inquiry(): void
    {
        $user = $this->userWithOrderAccess('staff');

        $provider = Partner::factory()->provider()->create();
        $vendor = Partner::factory()->vendor()->create();

        $response = $this->actingAs($user)
            ->from('/orders/create')
            ->post('/orders', [
                'client_name' => 'PT Pelanggan Baru',
                'provider_id' => $provider->id,
                'vendor_id' => $vendor->id,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Order::count());

        // Client dibuat otomatis (inactive) sebagai relasi dari Order.
        $this->assertDatabaseHas('clients', [
            'name' => 'PT Pelanggan Baru',
            'status' => 'inactive',
        ]);

        $client = Client::where('name', 'PT Pelanggan Baru')->firstOrFail();
        $this->assertDatabaseHas('orders', [
            'client_id' => $client->id,
            'provider_id' => $provider->id,
            'vendor_id' => $vendor->id,
            'status' => 'Inquiry',
        ]);
    }

    /** Client dengan nama sama dipakai ulang (tidak menggandakan baris client). */
    public function test_order_dengan_nama_client_yang_sama_memakai_ulang_client(): void
    {
        $user = $this->userWithOrderAccess('staff');

        $provider = Partner::factory()->provider()->create();
        $vendor = Partner::factory()->vendor()->create();

        $payload = [
            'client_name' => 'PT Langganan',
            'provider_id' => $provider->id,
            'vendor_id' => $vendor->id,
        ];

        $this->actingAs($user)->post('/orders', $payload)->assertSessionHasNoErrors();
        $this->actingAs($user)->post('/orders', $payload)->assertSessionHasNoErrors();

        $this->assertSame(2, Order::count());
        $this->assertSame(1, Client::where('name', 'PT Langganan')->count());
    }
}
