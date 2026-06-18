<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Package;
use App\Models\Partner;
use App\Models\User;
use App\Services\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test untuk H-05 — QW #6:
 *   "PO dari provider saya isi 100Mbps, kemudian ke tahap PO ke Vendor saya isi
 *    50Mbps maka PO dari provider berubah ke 50Mbps juga."
 *
 * Setelah perbaikan: kolom `bandwidth` (nilai dari PO_Provider) tidak boleh
 * tertimpa oleh submit form PO_Vendor. Nilai bandwidth vendor disimpan di
 * kolom terpisah (`vendor_bandwidth`) sehingga bisa dibandingkan dengan
 * bandwidth provider untuk alert mismatch (tanpa memblokir submit).
 *
 * Alur yang diuji:
 *   1. Buat Order, advance ke PO_Provider dengan bandwidth=100.
 *   2. Submit advance ke PO_Vendor dengan vendor_bandwidth=50.
 *   3. Verifikasi: bandwidth tetap 100, vendor_bandwidth=50, status=PO_Vendor.
 *   4. Submit advance ke Instalasi tanpa field (tahap berikutnya setelah PO_Vendor).
 *   5. Verifikasi: bandwidth tetap 100, vendor_bandwidth tetap 50.
 *
 * Catatan: penyertaan uji step 4 memastikan vendor_bandwidth tidak hilang
 * ketika pindah ke tahap selanjutnya (data harus persist).
 */
class OrderVendorBandwidthPreservationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithOrderAccess(): User
    {
        return User::factory()->create(['role' => 'staff']);
    }

    private function createOrderAt(string $status, array $overrides = []): Order
    {
        $client = Client::factory()->create();
        $provider = Partner::factory()->provider()->create();
        $vendor = Partner::factory()->vendor()->create();

        return Order::factory()->create(array_merge([
            'client_id' => $client->id,
            'provider_id' => $provider->id,
            'vendor_id' => $vendor->id,
            'status' => $status,
        ], $overrides));
    }

    public function test_po_vendor_does_not_overwrite_provider_bandwidth(): void
    {
        $user = $this->userWithOrderAccess();
        $package = Package::factory()->create();

        // 1. Order di tahap PO_Provider dengan bandwidth=100 Mbps (nilai dari PO_Provider).
        $order = $this->createOrderAt('PO_Provider', [
            'bandwidth' => '100',
            'package_id' => $package->id,
            'po_provider_number' => 'PO-P-001',
            'provider_otc' => 1_000_000,
            'provider_mrc' => 500_000,
        ]);

        $this->assertSame('100', (string) $order->fresh()->bandwidth, 'Prasyarat: bandwidth PO_Provider = 100.');

        // 2. Submit advance ke PO_Vendor dengan vendor_bandwidth=50 (berbeda dari provider).
        $response = $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->post(route('orders.advanceStatus', $order), [
                'status' => 'PO_Vendor',
                'po_vendor_number' => 'PO-V-001',
                'vendor_otc' => 400_000,
                'vendor_mrc' => 200_000,
                'vendor_bandwidth' => 50,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        // 3. Verifikasi: bandwidth provider tidak berubah, vendor_bandwidth tersimpan terpisah.
        $refreshed = $order->fresh();
        $this->assertSame('PO_Vendor', $refreshed->status, 'Status harus maju ke PO_Vendor.');
        $this->assertSame(
            '100',
            (string) $refreshed->bandwidth,
            'bandwidth PO_Provider (100) TIDAK BOLEH tertimpa oleh submit PO_Vendor.'
        );
        $this->assertSame(
            '50',
            (string) $refreshed->vendor_bandwidth,
            'vendor_bandwidth harus menyimpan nilai 50 yang diinput pada PO_Vendor.'
        );

        // 4. Submit advance ke tahap berikutnya (Instalasi) — tidak ada field khusus.
        $response2 = $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->post(route('orders.advanceStatus', $order), [
                'status' => 'Instalasi',
            ]);
        $response2->assertStatus(302);
        $response2->assertSessionHasNoErrors();

        // 5. Verifikasi kedua nilai tetap utuh setelah pindah tahap.
        $refreshed2 = $order->fresh();
        $this->assertSame('Instalasi', $refreshed2->status);
        $this->assertSame(
            '100',
            (string) $refreshed2->bandwidth,
            'bandwidth provider harus tetap 100 setelah pindah tahap.'
        );
        $this->assertSame(
            '50',
            (string) $refreshed2->vendor_bandwidth,
            'vendor_bandwidth harus tetap 50 setelah pindah tahap.'
        );
    }

    public function test_po_vendor_accepts_same_bandwidth_as_provider_without_error(): void
    {
        $user = $this->userWithOrderAccess();

        $order = $this->createOrderAt('PO_Provider', [
            'bandwidth' => '200',
        ]);

        // Submit PO_Vendor dengan vendor_bandwidth=200 (sama dengan provider) — harus sukses.
        $response = $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->post(route('orders.advanceStatus', $order), [
                'status' => 'PO_Vendor',
                'po_vendor_number' => 'PO-V-200',
                'vendor_otc' => 100_000,
                'vendor_mrc' => 100_000,
                'vendor_bandwidth' => 200,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $refreshed = $order->fresh();
        $this->assertSame('PO_Vendor', $refreshed->status);
        $this->assertSame('200', (string) $refreshed->bandwidth);
        $this->assertSame('200', (string) $refreshed->vendor_bandwidth);
    }

    public function test_po_vendor_requires_vendor_bandwidth_field(): void
    {
        $user = $this->userWithOrderAccess();

        $order = $this->createOrderAt('PO_Provider', [
            'bandwidth' => '100',
        ]);

        // Submit tanpa vendor_bandwidth — harus ditolak dengan validation error.
        $response = $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->post(route('orders.advanceStatus', $order), [
                'status' => 'PO_Vendor',
                'po_vendor_number' => 'PO-V-001',
                'vendor_otc' => 100_000,
                'vendor_mrc' => 100_000,
                // vendor_bandwidth sengaja tidak dikirim
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('vendor_bandwidth');
        $this->assertSame('PO_Provider', $order->fresh()->status);
    }
}