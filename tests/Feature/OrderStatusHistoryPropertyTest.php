<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Package;
use App\Models\Partner;
use App\Models\User;
use App\Services\OrderStatusService;
use Eris\Generators;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property test untuk pencatatan riwayat perubahan Status_Order (R6.4).
 *
 * Test ini menyentuh database dan HTTP (route orders.store + orders.advanceStatus
 * lewat OrderController), sehingga memakai Laravel TestCase + RefreshDatabase dan
 * acting-as seorang Pengguna dengan akses Modul_Order (role staff).
 *
 * Feature: talent-secreet-isp-broker, Property 9: Setiap perubahan status tercatat di riwayat
 *
 * Validates: Requirements 6.4
 *
 * Catatan penghitungan entri riwayat: OrderController@store membuat satu entri
 * riwayat awal 'Inquiry' saat Order dibuat. Setiap advance status yang sah
 * menambahkan tepat satu entri baru. Oleh karena itu untuk N transisi yang
 * dilakukan, total entri riwayat = 1 (entri awal Inquiry) + N. Urutan status
 * yang tercatat secara kronologis harus sama persis dengan rangkaian status
 * yang dikunjungi: [Inquiry, lalu N penerus berurutan].
 */
class OrderStatusHistoryPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    /**
     * Property 9: Untuk rangkaian N transisi status yang sah pada sebuah Order,
     * riwayat Order memuat tepat satu entri (status, waktu) untuk setiap
     * perubahan, dengan urutan dan jumlah entri sesuai banyaknya transisi yang
     * dilakukan ditambah entri awal Inquiry yang dicatat saat pembuatan.
     *
     * Generator: jumlah advance sah N acak dalam 0..6 (panjang maksimum rantai
     * 7 tahap dikurangi tahap awal). Setiap iterasi: buat Client/Provider/Vendor,
     * POST /orders (status Inquiry + 1 entri riwayat), lalu POST advance N kali
     * masing-masing memajukan ke penerus langsung via OrderStatusService::STATUSES.
     * Setelah tiap advance, pastikan satu baris riwayat baru ter-append. Terakhir,
     * verifikasi total entri = 1 + N dan urutan status kronologisnya cocok.
     *
     * Feature: talent-secreet-isp-broker, Property 9: Setiap perubahan status tercatat di riwayat
     * Validates: Requirements 6.4
     */
    public function testEveryStatusChangeIsRecordedInHistory(): void
    {
        $statuses = OrderStatusService::STATUSES;
        $maxAdvances = count($statuses) - 1; // 6

        $this->limitTo(100)
            ->forAll(
                Generators::choose(0, $maxAdvances) // jumlah transisi sah N (0..6)
            )
            ->then(function (int $advanceCount) use ($statuses): void {
                // Pengguna dengan akses Modul_Order.
                $user = User::factory()->create(['role' => 'staff']);

                $client = Client::factory()->create();
                $provider = Partner::factory()->provider()->create();
                $vendor = Partner::factory()->vendor()->create();
                $package = Package::factory()->create();

                // Buat Order via controller: status awal Inquiry + 1 entri riwayat.
                $createResponse = $this->actingAs($user)->post(route('orders.store'), [
                    'client_name' => $client->name,
                    'provider_id' => $provider->id,
                    'vendor_id' => $vendor->id,
                ]);
                $createResponse->assertRedirect();

                $order = Order::where('client_id', $client->id)->latest('id')->firstOrFail();

                // Prasyarat: status awal Inquiry dengan tepat satu entri riwayat.
                $this->assertSame('Inquiry', $order->fresh()->status);
                $this->assertSame(
                    1,
                    OrderStatusHistory::where('order_id', $order->id)->count(),
                    'Order baru harus punya tepat satu entri riwayat awal (Inquiry).'
                );

                // Rangkaian status yang dikunjungi, diawali entri awal Inquiry.
                $visited = ['Inquiry'];

                // Lakukan N transisi sah, masing-masing ke penerus langsung.
                for ($i = 0; $i < $advanceCount; $i++) {
                    $current = $statuses[$i];      // status sebelum advance ke-(i+1)
                    $target = $statuses[$i + 1];   // penerus langsung

                    $countBefore = OrderStatusHistory::where('order_id', $order->id)->count();

                    $response = $this->actingAs($user)->post(
                        route('orders.advanceStatus', $order),
                        ['status' => $target] + $this->stageFieldsFor($target, $package->id)
                    );
                    $response->assertRedirect();

                    // Status Order benar-benar maju ke target.
                    $this->assertSame(
                        $target,
                        $order->fresh()->status,
                        "Status Order harus maju dari {$current} ke {$target}."
                    );

                    // Tepat satu baris riwayat baru ter-append per perubahan.
                    $countAfter = OrderStatusHistory::where('order_id', $order->id)->count();
                    $this->assertSame(
                        $countBefore + 1,
                        $countAfter,
                        "Setiap perubahan status harus menambah tepat satu entri riwayat (transisi ke {$target})."
                    );

                    $visited[] = $target;
                }

                // Total entri riwayat = 1 (Inquiry awal) + N transisi.
                $histories = OrderStatusHistory::where('order_id', $order->id)
                    ->orderBy('changed_at')
                    ->orderBy('id')
                    ->get();

                $this->assertCount(
                    1 + $advanceCount,
                    $histories,
                    'Jumlah entri riwayat harus sama dengan 1 (Inquiry awal) + jumlah transisi.'
                );

                // Urutan status kronologis cocok dengan rangkaian yang dikunjungi.
                $this->assertSame(
                    $visited,
                    $histories->pluck('status')->all(),
                    'Urutan status pada riwayat harus sesuai rangkaian status yang dikunjungi.'
                );

                // Bersihkan data iterasi agar tidak terakumulasi antar iterasi Eris.
                OrderStatusHistory::where('order_id', $order->id)->delete();
                Order::whereKey($order->id)->delete();
                $client->delete();
                $provider->delete();
                $vendor->delete();
                $package->delete();
                $user->delete();
            });
    }

    /**
     * Field wajib yang harus dikirim saat memajukan order ke tahap tertentu
     * (mengikuti aturan validasi per-tahap di OrderController@advanceStatus).
     * Tahap tanpa field mengembalikan array kosong.
     *
     * @return array<string, mixed>
     */
    private function stageFieldsFor(string $target, int $packageId = 0): array
    {
        return match ($target) {
            'Penawaran' => ['offer_number' => 'PEN-001'],
            'PO_Provider' => [
                'package_id' => $packageId,
                'po_provider_number' => 'PO-P-001',
                'provider_otc' => 1_000_000,
                'provider_mrc' => 1_000_000,
                'bandwidth' => 50,
            ],
            'PO_Vendor' => [
                'po_vendor_number' => 'PO-V-001',
                'vendor_otc' => 500_000,
                'vendor_mrc' => 250_000,
            ],
            'BAA_BAST' => [
                'baa_number' => 'BAA-001',
                'bast_number' => 'BAST-001',
            ],
            'Client_Aktif' => ['contract_months' => 12],
            default => [],
        };
    }
}
