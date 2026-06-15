<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Partner;
use Eris\Generators;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property test untuk aktivasi otomatis Client saat salah satu Order-nya
 * mencapai status Complete.
 *
 * Aktivasi ditangani oleh App\Observers\OrderObserver yang terpasang ke
 * model Order via atribut #[ObservedBy]. Test ini menyentuh database
 * (Eloquent + observer), sehingga memakai Laravel TestCase + RefreshDatabase.
 *
 * Feature: talent-secreet-isp-broker, Property 4: Order yang mencapai Complete mengaktifkan Client-nya
 *
 * Validates: Requirements 4.5
 */
class ClientActivationPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    /**
     * Property 4: Untuk Client dengan sekumpulan Order miliknya, jika minimal
     * satu Order mencapai status Complete maka status Client menjadi 'active'.
     *
     * Generator: jumlah order acak (1..5) dan indeks acak salah satu order
     * yang akan ditransisikan ke Complete. Setiap iterasi membuat Client baru
     * (inactive) beserta Partner provider/vendor dan order-nya, lalu menyimpan
     * satu order dengan status Complete dan memverifikasi Client jadi aktif.
     *
     * Feature: talent-secreet-isp-broker, Property 4: Order yang mencapai Complete mengaktifkan Client-nya
     * Validates: Requirements 4.5
     */
    public function testOrderReachingCompleteActivatesItsClient(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generators::choose(1, 5), // jumlah order milik client
                Generators::choose(0, 4)  // benih indeks order yang jadi Complete
            )
            ->then(function (int $orderCount, int $completeSeed): void {
                $provider = Partner::factory()->provider()->create();
                $vendor = Partner::factory()->vendor()->create();
                $client = Client::factory()->create(['status' => 'inactive']);

                $orders = [];
                for ($i = 0; $i < $orderCount; $i++) {
                    $orders[] = Order::factory()->create([
                        'client_id' => $client->id,
                        'provider_id' => $provider->id,
                        'vendor_id' => $vendor->id,
                        'status' => 'Inquiry',
                    ]);
                }

                // Prasyarat: client masih inactive sebelum ada order Complete.
                $client->refresh();
                $this->assertSame('inactive', $client->status);

                // Transisikan satu order (indeks acak yang valid) ke Client_Aktif.
                $completeIndex = $completeSeed % $orderCount;
                $orders[$completeIndex]->status = 'Client_Aktif';
                $orders[$completeIndex]->save();

                // R4.5: client pemilik order Complete kini aktif.
                $client->refresh();
                $this->assertSame('active', $client->status);

                // Bersihkan baris yang dibuat agar iterasi tidak saling mengganggu.
                Order::where('client_id', $client->id)->delete();
                $client->delete();
                $provider->delete();
                $vendor->delete();
            });
    }

    /**
     * Konvers (opsional): jika tidak ada Order milik Client yang mencapai
     * Complete, Client tetap berstatus 'inactive'.
     *
     * Feature: talent-secreet-isp-broker, Property 4: Order yang mencapai Complete mengaktifkan Client-nya
     * Validates: Requirements 4.5
     */
    public function testClientStaysInactiveWhenNoOrderIsComplete(): void
    {
        $nonCompleteStatuses = [
            'Inquiry',
            'Cek_Ketersediaan',
            'Penawaran',
            'PO_Provider',
            'PO_Vendor',
            'Instalasi',
            'BAA_BAST',
            'BAST_Vendor',
        ];

        $this->limitTo(100)
            ->forAll(
                Generators::choose(1, 5),
                Generators::choose(0, count($nonCompleteStatuses) - 1)
            )
            ->then(function (int $orderCount, int $statusSeed) use ($nonCompleteStatuses): void {
                $provider = Partner::factory()->provider()->create();
                $vendor = Partner::factory()->vendor()->create();
                $client = Client::factory()->create(['status' => 'inactive']);

                $createdOrderIds = [];
                for ($i = 0; $i < $orderCount; $i++) {
                    $status = $nonCompleteStatuses[($statusSeed + $i) % count($nonCompleteStatuses)];
                    $order = Order::factory()->create([
                        'client_id' => $client->id,
                        'provider_id' => $provider->id,
                        'vendor_id' => $vendor->id,
                        'status' => $status,
                    ]);
                    $createdOrderIds[] = $order->id;
                }

                // Tanpa order Complete, client tetap inactive.
                $client->refresh();
                $this->assertSame('inactive', $client->status);

                Order::whereIn('id', $createdOrderIds)->delete();
                $client->delete();
                $provider->delete();
                $vendor->delete();
            });
    }
}
